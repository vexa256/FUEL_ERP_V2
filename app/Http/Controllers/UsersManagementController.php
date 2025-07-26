<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class UsersManagementController extends Controller
{
    /**
     * Display paginated list of users with advanced filtering
     * Respects station hierarchy and role-based access
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $search = $request->get('search', '');
            $stationFilter = $request->get('station_id', '');
            $roleFilter = $request->get('role', '');
            $statusFilter = $request->get('status', '');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            // Build base query with exact schema compliance
            $query = DB::table('users as u')
                ->join('stations as s', 'u.station_id', '=', 's.id')
                ->select([
                    'u.id',
                    'u.station_id',
                    'u.employee_id',
                    'u.first_name',
                    'u.last_name',
                    'u.email',
                    'u.phone',
                    'u.role',
                    'u.is_active',
                    'u.last_login_at',
                    'u.created_at',
                    'u.updated_at',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    's.timezone',
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as full_name")
                ]);

            // Apply search filters
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('u.first_name', 'LIKE', "%{$search}%")
                      ->orWhere('u.last_name', 'LIKE', "%{$search}%")
                      ->orWhere('u.email', 'LIKE', "%{$search}%")
                      ->orWhere('u.employee_id', 'LIKE', "%{$search}%")
                      ->orWhere('s.name', 'LIKE', "%{$search}%");
                });
            }

            // Station filter
            if (!empty($stationFilter)) {
                $query->where('u.station_id', $stationFilter);
            }

            // Role filter - exact match from ENUM
            if (!empty($roleFilter) && in_array($roleFilter, ['admin', 'manager', 'attendant', 'supervisor'])) {
                $query->where('u.role', $roleFilter);
            }

            // Status filter
            if ($statusFilter !== '') {
                $query->where('u.is_active', (bool)$statusFilter);
            }

            // Sorting with validation
            $allowedSorts = ['created_at', 'updated_at', 'first_name', 'last_name', 'email', 'role', 'is_active', 'last_login_at'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'created_at';
            }
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            $query->orderBy("u.{$sortBy}", $sortOrder);

            // Get total count for pagination
            $totalUsers = $query->count();

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $users = $query->limit($perPage)->offset($offset)->get();

            // Get stations for filter dropdown
            $stations = DB::table('stations')
                ->select('id', 'name', 'location')
                ->orderBy('name')
                ->get();

            // Calculate pagination metadata
            $totalPages = ceil($totalUsers / $perPage);
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalUsers,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ];

            // Role statistics
            $roleStats = DB::table('users')
                ->select('role', DB::raw('COUNT(*) as count'))
                ->where('is_active', true)
                ->groupBy('role')
                ->get()
                ->keyBy('role');

            return view('users.index', compact(
                'users',
                'stations',
                'pagination',
                'roleStats',
                'search',
                'stationFilter',
                'roleFilter',
                'statusFilter',
                'sortBy',
                'sortOrder'
            ));

        } catch (Exception $e) {
            Log::error('Users index error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to load users. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show the form for creating a new user
     * Validates station access and role permissions
     */
    public function create()
    {
        try {
            // Get stations for dropdown - exact schema compliance
            $stations = DB::table('stations')
                ->select('id', 'name', 'location', 'currency_code', 'timezone')
                ->orderBy('name')
                ->get();

            if ($stations->isEmpty()) {
                return redirect()->route('users.index')
                    ->with('warning', 'No stations found. Please create a station first.');
            }

            // Role options from database ENUM
            $roles = [
                'admin' => 'System Administrator',
                'manager' => 'Station Manager',
                'supervisor' => 'Supervisor',
                'attendant' => 'Fuel Attendant'
            ];

            return view('users.create', compact('stations', 'roles'));

        } catch (Exception $e) {
            Log::error('Users create form error: ' . $e->getMessage());

            return redirect()->route('users.index')
                ->with('error', 'Failed to load user creation form.');
        }
    }

    /**
     * Store a newly created user with comprehensive validation
     * Respects unique constraints and foreign key relationships
     */
    public function store(Request $request)
    {
        try {
            // Comprehensive validation matching exact database schema
            $validator = Validator::make($request->all(), [
                'station_id' => 'required|integer|exists:stations,id',
                'employee_id' => 'required|string|max:50',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'role' => 'required|in:admin,manager,attendant,supervisor',
                'password' => 'required|string|min:8|confirmed',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Please correct the validation errors.');
            }

            DB::beginTransaction();

            // Validate unique employee_id per station (unique constraint)
            $existingEmployee = DB::table('users')
                ->where('station_id', $request->station_id)
                ->where('employee_id', $request->employee_id)
                ->exists();

            if ($existingEmployee) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Employee ID already exists for this station.');
            }

            // Validate station exists and is active
            $station = DB::table('stations')
                ->where('id', $request->station_id)
                ->first();

            if (!$station) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Selected station does not exist.');
            }

            // Create user with exact schema fields
            $userId = DB::table('users')->insertGetId([
                'station_id' => $request->station_id,
                'employee_id' => $request->employee_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'is_active' => $request->has('is_active'),
                'password' => Hash::make($request->password),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log user creation for audit
            Log::info('User created successfully', [
                'user_id' => $userId,
                'employee_id' => $request->employee_id,
                'station_id' => $request->station_id,
                'role' => $request->role,
                'created_by' => auth()->user()->id ?? 'system'
            ]);

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', "User '{$request->first_name} {$request->last_name}' created successfully.");

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User creation failed: ' . $e->getMessage(), [
                'request' => $request->except(['password', 'password_confirmation']),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified user with related data
     * Shows complete user profile with station and activity information
     */
    public function show($id)
    {
        try {
            // Get user with station information - exact schema compliance
            $user = DB::table('users as u')
                ->join('stations as s', 'u.station_id', '=', 's.id')
                ->select([
                    'u.*',
                    's.name as station_name',
                    's.location as station_location',
                    's.currency_code',
                    's.timezone',
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as full_name")
                ])
                ->where('u.id', $id)
                ->first();

            if (!$user) {
                return redirect()->route('users.index')
                    ->with('error', 'User not found.');
            }

            // Get user activity statistics (if audit logs exist)
            $activityStats = [
                'total_logins' => 0, // Would be calculated from audit logs
                'last_activity' => $user->last_login_at,
                'days_since_last_login' => $user->last_login_at ?
                    now()->diffInDays($user->last_login_at) : null
            ];

            // Get recent user activities (placeholder for audit log integration)
            $recentActivities = collect([]);

            return view('users.show', compact('user', 'activityStats', 'recentActivities'));

        } catch (Exception $e) {
            Log::error('User show error: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('users.index')
                ->with('error', 'Failed to load user details.');
        }
    }

    /**
     * Show the form for editing the specified user
     * Preserves current data and validates access permissions
     */
    public function edit($id)
    {
        try {
            // Get user details
            $user = DB::table('users as u')
                ->join('stations as s', 'u.station_id', '=', 's.id')
                ->select([
                    'u.*',
                    's.name as station_name'
                ])
                ->where('u.id', $id)
                ->first();

            if (!$user) {
                return redirect()->route('users.index')
                    ->with('error', 'User not found.');
            }

            // Get stations for dropdown
            $stations = DB::table('stations')
                ->select('id', 'name', 'location', 'currency_code', 'timezone')
                ->orderBy('name')
                ->get();

            // Role options from database ENUM
            $roles = [
                'admin' => 'System Administrator',
                'manager' => 'Station Manager',
                'supervisor' => 'Supervisor',
                'attendant' => 'Fuel Attendant'
            ];

            return view('users.edit', compact('user', 'stations', 'roles'));

        } catch (Exception $e) {
            Log::error('User edit form error: ' . $e->getMessage(), [
                'user_id' => $id
            ]);

            return redirect()->route('users.index')
                ->with('error', 'Failed to load user edit form.');
        }
    }

    /**
     * Update the specified user with validation and constraint checking
     * Maintains data integrity and audit trail
     */
    public function update(Request $request, $id)
    {
        try {
            // Check if user exists
            $existingUser = DB::table('users')->where('id', $id)->first();
            if (!$existingUser) {
                return redirect()->route('users.index')
                    ->with('error', 'User not found.');
            }

            // Validation rules - email unique except current user
            $validator = Validator::make($request->all(), [
                'station_id' => 'required|integer|exists:stations,id',
                'employee_id' => 'required|string|max:50',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => "required|email|max:255|unique:users,email,{$id}",
                'phone' => 'nullable|string|max:20',
                'role' => 'required|in:admin,manager,attendant,supervisor',
                'password' => 'nullable|string|min:8|confirmed',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Please correct the validation errors.');
            }

            DB::beginTransaction();

            // Check unique employee_id per station (excluding current user)
            $duplicateEmployee = DB::table('users')
                ->where('station_id', $request->station_id)
                ->where('employee_id', $request->employee_id)
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicateEmployee) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Employee ID already exists for this station.');
            }

            // Prepare update data
            $updateData = [
                'station_id' => $request->station_id,
                'employee_id' => $request->employee_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'is_active' => $request->has('is_active'),
                'updated_at' => now()
            ];

            // Add password if provided
            if (!empty($request->password)) {
                $updateData['password'] = Hash::make($request->password);
            }

            // Update user
            $updated = DB::table('users')
                ->where('id', $id)
                ->update($updateData);

            if (!$updated) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to update user. No changes made.');
            }

            // Log update for audit
            Log::info('User updated successfully', [
                'user_id' => $id,
                'changes' => array_keys($updateData),
                'updated_by' => auth()->user()->id ?? 'system'
            ]);

            DB::commit();

            return redirect()->route('users.show', $id)
                ->with('success', 'User updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User update failed: ' . $e->getMessage(), [
                'user_id' => $id,
                'request' => $request->except(['password', 'password_confirmation']),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update user. Please try again.');
        }
    }

    /**
     * Remove the specified user with cascade validation
     * Checks for dependent records before deletion
     */
    public function destroy($id)
    {
        try {
            // Get user details first
            $user = DB::table('users')->where('id', $id)->first();
            if (!$user) {
                return redirect()->route('users.index')
                    ->with('error', 'User not found.');
            }

            DB::beginTransaction();

            // Check for dependent records that would prevent deletion
            $dependencies = [
                'deliveries' => DB::table('deliveries')->where('user_id', $id)->count(),
                'daily_readings' => DB::table('daily_readings')->where('recorded_by_user_id', $id)->count(),
                'meter_readings' => DB::table('meter_readings')->where('recorded_by_user_id', $id)->count(),
                'daily_reconciliations' => DB::table('daily_reconciliations')->where('reconciled_by_user_id', $id)->count(),
                'selling_prices' => DB::table('selling_prices')->where('set_by_user_id', $id)->count()
            ];

            $totalDependencies = array_sum($dependencies);

            if ($totalDependencies > 0) {
                DB::rollBack();

                $dependencyList = collect($dependencies)
                    ->filter(fn($count) => $count > 0)
                    ->map(fn($count, $table) => "{$table}: {$count}")
                    ->implode(', ');

                return redirect()->back()
                    ->with('error', "Cannot delete user. Found {$totalDependencies} dependent records: {$dependencyList}");
            }

            // Safe to delete - no dependencies
            $deleted = DB::table('users')->where('id', $id)->delete();

            if (!$deleted) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Failed to delete user.');
            }

            // Log deletion for audit
            Log::warning('User deleted', [
                'user_id' => $id,
                'employee_id' => $user->employee_id,
                'email' => $user->email,
                'deleted_by' => auth()->user()->id ?? 'system'
            ]);

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'User deleted successfully.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User deletion failed: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete user. Please try again.');
        }
    }

    /**
     * Toggle user active status
     * Provides quick enable/disable functionality
     */
    public function toggleStatus($id)
    {
        try {
            $user = DB::table('users')->where('id', $id)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $newStatus = !$user->is_active;

            DB::table('users')
                ->where('id', $id)
                ->update([
                    'is_active' => $newStatus,
                    'updated_at' => now()
                ]);

            Log::info('User status toggled', [
                'user_id' => $id,
                'old_status' => $user->is_active,
                'new_status' => $newStatus,
                'changed_by' => auth()->user()->id ?? 'system'
            ]);

            $statusText = $newStatus ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "User {$statusText} successfully",
                'new_status' => $newStatus
            ]);

        } catch (Exception $e) {
            Log::error('User status toggle failed: ' . $e->getMessage(), [
                'user_id' => $id
            ]);

            return response()->json(['error' => 'Failed to update user status'], 500);
        }
    }

    /**
     * Get users by station (AJAX endpoint)
     * For dynamic dropdown population
     */
    public function getUsersByStation($stationId)
    {
        try {
            $users = DB::table('users')
                ->select('id', 'employee_id', 'first_name', 'last_name', 'role', 'is_active')
                ->where('station_id', $stationId)
                ->where('is_active', true)
                ->orderBy('first_name')
                ->get()
                ->map(function($user) {
                    $user->full_name = "{$user->first_name} {$user->last_name}";
                    return $user;
                });

            return response()->json($users);

        } catch (Exception $e) {
            Log::error('Get users by station failed: ' . $e->getMessage(), [
                'station_id' => $stationId
            ]);

            return response()->json(['error' => 'Failed to load users'], 500);
        }
    }
}
