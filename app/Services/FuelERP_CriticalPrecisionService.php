<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * FUEL_ERP_V2 Critical Precision Service - FULLY SCHEMA COMPLIANT
 *
 * UPDATED: Based on latest schema dump analysis
 * ZERO PHANTOM FIELDS: All operations validated against ACTUAL database structure
 * SHELL UGANDA COMPLIANT: Supports full expanded fuel type range
 */
class FuelERP_CriticalPrecisionService
{
    /**
     * ACTUAL schema-validated field mappings - UPDATED FROM REAL DATABASE
     */
    private const SCHEMA_FIELDS = [
        'stations' => ['id', 'name', 'location', 'currency_code', 'timezone', 'created_at', 'updated_at'],
        'fifo_consumption_log' => ['id', 'reconciliation_id', 'fifo_layer_id', 'volume_consumed_liters', 'cost_per_liter_ugx', 'total_cost_ugx', 'consumption_sequence', 'created_at', 'inventory_value_before_ugx', 'inventory_value_after_ugx', 'weighted_avg_cost_ugx', 'valuation_impact_ugx'],
        'tanks' => ['id', 'station_id', 'tank_number', 'fuel_type', 'capacity_liters', 'current_volume_liters', 'created_at', 'updated_at'],
        'stock_alert_thresholds' => ['id', 'tank_id', 'low_stock_percentage', 'critical_stock_percentage', 'reorder_point_liters', 'is_active', 'created_at', 'updated_at'],
        'users' => ['id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'station_id', 'employee_id', 'first_name', 'last_name', 'phone', 'role', 'is_active', 'last_login_at', 'created_at', 'updated_at'],
        'selling_prices' => ['id', 'station_id', 'fuel_type', 'price_per_liter_ugx', 'effective_from_date', 'effective_to_date', 'is_active', 'set_by_user_id', 'created_at'],
        'deliveries' => ['id', 'tank_id', 'user_id', 'delivery_reference', 'volume_liters', 'cost_per_liter_ugx', 'total_cost_ugx', 'delivery_date', 'delivery_time', 'supplier_name', 'invoice_number', 'created_at'],
        'daily_reconciliations' => ['id', 'tank_id', 'reconciliation_date', 'opening_stock_liters', 'total_delivered_liters', 'total_dispensed_liters', 'theoretical_closing_stock_liters', 'actual_closing_stock_liters', 'volume_variance_liters', 'variance_percentage', 'total_cogs_ugx', 'total_sales_ugx', 'gross_profit_ugx', 'profit_margin_percentage', 'reconciled_by_user_id', 'reconciled_at', 'abs_variance_percentage', 'opening_stock_value_ugx', 'closing_stock_value_ugx', 'inventory_value_change_ugx', 'cost_of_goods_available_ugx', 'valuation_method', 'valuation_quality', 'inventory_variance_ugx', 'valuation_processed_at', 'valuation_processed_by', 'requires_revaluation', 'valuation_error_message'],
        'meters' => ['id', 'tank_id', 'meter_number', 'current_reading_liters', 'is_active', 'created_at', 'updated_at'],
        'meter_readings' => ['id', 'meter_id', 'reading_date', 'opening_reading_liters', 'closing_reading_liters', 'dispensed_liters', 'recorded_by_user_id', 'created_at', 'updated_at'],
        'daily_readings' => ['id', 'tank_id', 'reading_date', 'morning_dip_liters', 'evening_dip_liters', 'water_level_mm', 'temperature_celsius', 'recorded_by_user_id', 'created_at', 'updated_at', 'calculation_method'],
        'fifo_layers' => ['id', 'tank_id', 'delivery_id', 'layer_sequence', 'original_volume_liters', 'remaining_volume_liters', 'cost_per_liter_ugx', 'delivery_date', 'is_exhausted', 'created_at', 'updated_at', 'original_value_ugx', 'remaining_value_ugx', 'consumed_value_ugx', 'market_value_per_liter_ugx', 'lcm_adjustment_ugx', 'layer_status', 'valuation_last_updated'],
        'notifications' => ['id', 'station_id', 'tank_id', 'meter_id', 'notification_type', 'severity', 'title', 'message', 'variance_magnitude', 'variance_percentage', 'notification_date', 'status', 'resolution_notes', 'created_by_system', 'resolved_by_user_id', 'resolved_at', 'created_at', 'updated_at'],
        'audit_log' => ['id', 'table_name', 'record_id', 'action', 'old_values', 'new_values', 'user_id', 'ip_address', 'user_agent', 'created_at'],
        'financial_ledger' => ['id', 'station_id', 'entry_date', 'account_type', 'fuel_type', 'debit_amount_ugx', 'credit_amount_ugx', 'description', 'reference_table', 'reference_id', 'reconciliation_id', 'created_at'],
        'market_prices' => ['id', 'station_id', 'fuel_type', 'price_date', 'market_price_per_liter_ugx', 'price_source', 'source_reference', 'price_quality', 'effective_from_timestamp', 'effective_to_timestamp', 'is_active', 'created_by_user_id', 'created_at', 'updated_at'],
        'price_change_log' => ['id', 'station_id', 'fuel_type', 'old_price_ugx', 'new_price_ugx', 'price_change_ugx', 'price_change_percentage', 'effective_date', 'changed_by_user_id', 'estimated_margin_impact_ugx', 'created_at']
    ];

    /**
     * ACTUAL enum values from current schema - EXPANDED SHELL UGANDA LINEUP
     */
    private const VALID_ENUMS = [
        // FULL SHELL UGANDA FUEL TYPE RANGE - ACTUAL FROM SCHEMA
        'fuel_type_full' => [
            'petrol', 'diesel', 'kerosene', 'fuelsave_unleaded', 'fuelsave_diesel',
            'v_power_unleaded', 'v_power_diesel', 'ago', 'super_unleaded', 'jet_a1',
            'avgas_100ll', 'heavy_fuel_oil', 'marine_gas_oil', 'low_sulfur_diesel',
            'ultra_low_sulfur_diesel', 'lpg', 'cooking_gas', 'industrial_lpg',
            'autogas', 'household_kerosene', 'illuminating_kerosene', 'industrial_kerosene'
        ],
        // LIMITED FUEL TYPES for price_change_log ONLY
        'fuel_type_limited' => ['petrol', 'diesel', 'kerosene'],
        'user_role' => ['admin', 'manager', 'attendant', 'supervisor'],
        'notification_severity' => ['low', 'medium', 'high', 'critical'],
        'notification_type' => ['volume_variance', 'meter_variance', 'low_stock', 'system_error', 'fifo_exhausted'],
        'notification_status' => ['open', 'investigating', 'resolved'],
        'valuation_method' => ['FIFO', 'WEIGHTED_AVERAGE', 'ESTIMATED', 'MANUAL'],
        'valuation_quality' => ['COMPLETE', 'ESTIMATED_MINOR', 'ESTIMATED_MAJOR', 'RECOVERY_MODE'],
        'audit_action' => ['INSERT', 'UPDATE', 'DELETE'],
        'account_type' => ['revenue', 'cogs', 'inventory', 'variance_loss', 'variance_gain'],
        'price_source' => ['MANUAL', 'API_FEED', 'CALCULATED', 'ESTIMATED'],
        'price_quality' => ['VERIFIED', 'ESTIMATED', 'STALE', 'SUSPECT'],
        'layer_status' => ['ACTIVE', 'DEPLETED', 'ADJUSTED', 'WRITTEN_DOWN']
    ];

    /**
     * CRITICAL: Table-specific fuel type validation
     */
    private function validateFuelType(string $table, string $fuelType): void
    {
        $validTypes = match($table) {
            'price_change_log' => self::VALID_ENUMS['fuel_type_limited'],
            default => self::VALID_ENUMS['fuel_type_full']
        };

        if (!in_array($fuelType, $validTypes)) {
            $validList = implode(', ', $validTypes);
            throw new Exception("INVALID FUEL TYPE for $table: '$fuelType'. Valid: $validList");
        }
    }

    /**
     * Validate data against ACTUAL schema
     */
    private function validateSchemaCompliance(string $table, array $data): void
    {
        if (!isset(self::SCHEMA_FIELDS[$table])) {
            throw new Exception("SCHEMA VALIDATION FAILED: Unknown table '$table'");
        }

        $allowedFields = self::SCHEMA_FIELDS[$table];
        $phantomFields = array_diff(array_keys($data), $allowedFields);

        if (!empty($phantomFields)) {
            $phantomList = implode(', ', $phantomFields);
            Log::critical("PHANTOM FIELDS DETECTED", [
                'table' => $table,
                'phantom_fields' => $phantomFields,
                'attempted_data' => $data,
                'allowed_fields' => $allowedFields
            ]);
            throw new Exception("PHANTOM FIELDS DETECTED in $table: $phantomList");
        }

        // Validate fuel type if present
        if (isset($data['fuel_type'])) {
            $this->validateFuelType($table, $data['fuel_type']);
        }
    }

    /**
     * Create stock alert thresholds - FULLY SCHEMA COMPLIANT
     */
    public function createStockAlertThresholds(int $tankId, array $thresholds): bool
    {
        try {
            // Validate tank exists
            $tank = DB::table('tanks')->where('id', $tankId)->first();
            if (!$tank) {
                throw new Exception("Tank ID $tankId not found");
            }

            // Calculate reorder point (15% of capacity by default)
            $reorderPointLiters = round($tank->capacity_liters * 0.15, 3);

            $data = [
                'tank_id' => $tankId,
                'low_stock_percentage' => $thresholds['low_stock_percentage'] ?? 20.00,
                'critical_stock_percentage' => $thresholds['critical_stock_percentage'] ?? 10.00,
                'reorder_point_liters' => $thresholds['reorder_point_liters'] ?? $reorderPointLiters,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // CRITICAL: Schema validation
            $this->validateSchemaCompliance('stock_alert_thresholds', $data);

            $result = DB::table('stock_alert_thresholds')->insert($data);

            // Log to audit trail
            $this->logAudit('stock_alert_thresholds', $tankId, 'INSERT', null, $data, auth()->id());

            return $result;

        } catch (Exception $e) {
            Log::error("Stock alert thresholds creation failed", [
                'tank_id' => $tankId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create delivery with EXPANDED FUEL TYPE support
     */
    public function createDelivery(array $deliveryData): int
    {
        DB::beginTransaction();
        try {
            // Validate required fields
            $requiredFields = ['tank_id', 'user_id', 'volume_liters', 'cost_per_liter_ugx', 'delivery_date'];
            foreach ($requiredFields as $field) {
                if (!isset($deliveryData[$field])) {
                    throw new Exception("Required field missing: $field");
                }
            }

            // Validate tank exists and get its fuel type
            $tank = DB::table('tanks')->where('id', $deliveryData['tank_id'])->first();
            if (!$tank) {
                throw new Exception("Tank ID {$deliveryData['tank_id']} not found");
            }

            // Validate fuel type compatibility
            $this->validateFuelType('tanks', $tank->fuel_type);

            // Generate unique delivery reference
            $deliveryReference = 'DEL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Ensure uniqueness
            while (DB::table('deliveries')->where('delivery_reference', $deliveryReference)->exists()) {
                $deliveryReference = 'DEL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }

            $totalCost = round($deliveryData['volume_liters'] * $deliveryData['cost_per_liter_ugx'], 4);

            $data = [
                'tank_id' => $deliveryData['tank_id'],
                'user_id' => $deliveryData['user_id'],
                'delivery_reference' => $deliveryReference,
                'volume_liters' => round($deliveryData['volume_liters'], 3),
                'cost_per_liter_ugx' => round($deliveryData['cost_per_liter_ugx'], 4),
                'total_cost_ugx' => $totalCost,
                'delivery_date' => $deliveryData['delivery_date'],
                'delivery_time' => $deliveryData['delivery_time'] ?? now()->format('H:i:s'),
                'supplier_name' => $deliveryData['supplier_name'] ?? null,
                'invoice_number' => $deliveryData['invoice_number'] ?? null,
                'created_at' => now()
            ];

            // CRITICAL: Schema validation
            $this->validateSchemaCompliance('deliveries', $data);

            $deliveryId = DB::table('deliveries')->insertGetId($data);

            // Update tank volume
            DB::table('tanks')
                ->where('id', $deliveryData['tank_id'])
                ->increment('current_volume_liters', $deliveryData['volume_liters']);

            // Create FIFO layer
            $this->createFifoLayer($deliveryData['tank_id'], $deliveryId, $deliveryData);

            // Create stock alert thresholds if not exist
            $this->ensureStockAlertThresholds($deliveryData['tank_id']);

            // Log to audit
            $this->logAudit('deliveries', $deliveryId, 'INSERT', null, $data, $deliveryData['user_id']);

            DB::commit();
            return $deliveryId;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Delivery creation failed", [
                'error' => $e->getMessage(),
                'data' => $deliveryData,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create FIFO layer with proper validation
     */
    private function createFifoLayer(int $tankId, int $deliveryId, array $deliveryData): void
    {
        // Get next layer sequence
        $maxSequence = DB::table('fifo_layers')
            ->where('tank_id', $tankId)
            ->max('layer_sequence') ?? 0;

        $originalValue = round($deliveryData['volume_liters'] * $deliveryData['cost_per_liter_ugx'], 4);

        $layerData = [
            'tank_id' => $tankId,
            'delivery_id' => $deliveryId,
            'layer_sequence' => $maxSequence + 1,
            'original_volume_liters' => round($deliveryData['volume_liters'], 3),
            'remaining_volume_liters' => round($deliveryData['volume_liters'], 3),
            'cost_per_liter_ugx' => round($deliveryData['cost_per_liter_ugx'], 4),
            'delivery_date' => $deliveryData['delivery_date'],
            'is_exhausted' => false,
            'created_at' => now(),
            'updated_at' => now(),
            'original_value_ugx' => $originalValue,
            'remaining_value_ugx' => $originalValue,
            'consumed_value_ugx' => 0.0000,
            'market_value_per_liter_ugx' => null,
            'lcm_adjustment_ugx' => null,
            'layer_status' => 'ACTIVE',
            'valuation_last_updated' => now()
        ];

        $this->validateSchemaCompliance('fifo_layers', $layerData);
        DB::table('fifo_layers')->insert($layerData);
    }

    /**
     * Ensure stock alert thresholds exist for tank
     */
    private function ensureStockAlertThresholds(int $tankId): void
    {
        $exists = DB::table('stock_alert_thresholds')->where('tank_id', $tankId)->exists();

        if (!$exists) {
            $this->createStockAlertThresholds($tankId, []);
        }
    }

    /**
     * Log audit trail with ACTUAL schema fields
     */
    private function logAudit(string $tableName, int $recordId, string $action, ?array $oldValues, array $newValues, ?int $userId): void
    {
        $auditData = [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => json_encode($newValues),
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now()
        ];

        $this->validateSchemaCompliance('audit_log', $auditData);
        DB::table('audit_log')->insert($auditData);
    }

    /**
     * Create selling price with expanded fuel type support
     */
    public function createSellingPrice(array $priceData): int
    {
        try {
            // Validate required fields
            $requiredFields = ['station_id', 'fuel_type', 'price_per_liter_ugx', 'effective_from_date', 'set_by_user_id'];
            foreach ($requiredFields as $field) {
                if (!isset($priceData[$field])) {
                    throw new Exception("Required field missing: $field");
                }
            }

            // Validate station exists
            $station = DB::table('stations')->where('id', $priceData['station_id'])->first();
            if (!$station) {
                throw new Exception("Station ID {$priceData['station_id']} not found");
            }

            // Deactivate existing prices for this fuel type
            DB::table('selling_prices')
                ->where('station_id', $priceData['station_id'])
                ->where('fuel_type', $priceData['fuel_type'])
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'effective_to_date' => now()->subSecond()->format('Y-m-d')
                ]);

            $data = [
                'station_id' => $priceData['station_id'],
                'fuel_type' => $priceData['fuel_type'],
                'price_per_liter_ugx' => round($priceData['price_per_liter_ugx'], 4),
                'effective_from_date' => $priceData['effective_from_date'],
                'effective_to_date' => $priceData['effective_to_date'] ?? null,
                'is_active' => true,
                'set_by_user_id' => $priceData['set_by_user_id'],
                'created_at' => now()
            ];

            $this->validateSchemaCompliance('selling_prices', $data);
            $priceId = DB::table('selling_prices')->insertGetId($data);

            // Log to audit
            $this->logAudit('selling_prices', $priceId, 'INSERT', null, $data, $priceData['set_by_user_id']);

            return $priceId;

        } catch (Exception $e) {
            Log::error("Selling price creation failed", [
                'error' => $e->getMessage(),
                'data' => $priceData
            ]);
            throw $e;
        }
    }

    /**
     * Get current active selling price for fuel type
     */
    public function getCurrentSellingPrice(int $stationId, string $fuelType): ?float
    {
        $this->validateFuelType('selling_prices', $fuelType);

        $price = DB::table('selling_prices')
            ->where('station_id', $stationId)
            ->where('fuel_type', $fuelType)
            ->where('is_active', true)
            ->where('effective_from_date', '<=', now()->format('Y-m-d'))
            ->where(function($query) {
                $query->whereNull('effective_to_date')
                      ->orWhere('effective_to_date', '>=', now()->format('Y-m-d'));
            })
            ->value('price_per_liter_ugx');

        return $price ? (float) $price : null;
    }

    /**
     * Calculate dispensed volume from meter readings
     */
    private function calculateTotalDispensed(int $tankId, Carbon $date): float
    {
        $totalDispensed = DB::table('meter_readings')
            ->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
            ->where('meters.tank_id', $tankId)
            ->where('meter_readings.reading_date', $date->format('Y-m-d'))
            ->where('meters.is_active', true)
            ->sum('meter_readings.dispensed_liters');

        return (float) ($totalDispensed ?? 0);
    }

    /**
     * Get tanks by station with fuel type filtering
     */
    public function getTanksByStation(int $stationId, ?string $fuelType = null): array
    {
        $query = DB::table('tanks')
            ->where('station_id', $stationId);

        if ($fuelType) {
            $this->validateFuelType('tanks', $fuelType);
            $query->where('fuel_type', $fuelType);
        }

        return $query->get()->toArray();
    }

    /**
     * Get supported fuel types for a table
     */
    public function getSupportedFuelTypes(string $table = 'tanks'): array
    {
        return match($table) {
            'price_change_log' => self::VALID_ENUMS['fuel_type_limited'],
            default => self::VALID_ENUMS['fuel_type_full']
        };
    }

    /**
     * MISSING METHOD 1: Generic enum validation
     */
    private function validateEnumValue(string $field, $value): void
    {
        $enumMap = [
            'fuel_type' => self::VALID_ENUMS['fuel_type_full'],
            'fuel_type_limited' => self::VALID_ENUMS['fuel_type_limited'],
            'role' => self::VALID_ENUMS['user_role'],
            'severity' => self::VALID_ENUMS['notification_severity'],
            'notification_type' => self::VALID_ENUMS['notification_type'],
            'status' => self::VALID_ENUMS['notification_status'],
            'valuation_method' => self::VALID_ENUMS['valuation_method'],
            'valuation_quality' => self::VALID_ENUMS['valuation_quality'],
            'action' => self::VALID_ENUMS['audit_action'],
            'account_type' => self::VALID_ENUMS['account_type'],
            'price_source' => self::VALID_ENUMS['price_source'],
            'price_quality' => self::VALID_ENUMS['price_quality'],
            'layer_status' => self::VALID_ENUMS['layer_status']
        ];

        if (isset($enumMap[$field]) && !in_array($value, $enumMap[$field])) {
            $validValues = implode(', ', $enumMap[$field]);
            throw new Exception("INVALID ENUM VALUE for $field: '$value'. Valid values: $validValues");
        }
    }

    /**
     * MISSING METHOD 2: Process daily reconciliation with full business logic
     */
    public function processDailyReconciliation(int $tankId, Carbon $reconciliationDate, array $reconciliationData): int
    {
        DB::beginTransaction();
        try {
            // Validate required fields
            $requiredFields = ['actual_closing_stock_liters', 'total_sales_ugx', 'reconciled_by_user_id'];
            foreach ($requiredFields as $field) {
                if (!isset($reconciliationData[$field])) {
                    throw new Exception("Required field missing: $field");
                }
            }

            // Get opening stock from previous day or calculate
            $openingStock = $this->calculateOpeningStock($tankId, $reconciliationDate);

            // Get deliveries for the day
            $totalDelivered = DB::table('deliveries')
                ->where('tank_id', $tankId)
                ->where('delivery_date', $reconciliationDate->format('Y-m-d'))
                ->sum('volume_liters') ?? 0;

            // Get meter readings to calculate dispensed volume
            $totalDispensed = $this->calculateTotalDispensed($tankId, $reconciliationDate);

            // Calculate COGS using FIFO
            $cogsData = $this->calculateFifoCogs($tankId, $totalDispensed);

            $data = [
                'tank_id' => $tankId,
                'reconciliation_date' => $reconciliationDate->format('Y-m-d'),
                'opening_stock_liters' => round($openingStock, 3),
                'total_delivered_liters' => round($totalDelivered, 3),
                'total_dispensed_liters' => round($totalDispensed, 3),
                'actual_closing_stock_liters' => round($reconciliationData['actual_closing_stock_liters'], 3),
                'total_cogs_ugx' => round($cogsData['total_cogs'], 4),
                'total_sales_ugx' => round($reconciliationData['total_sales_ugx'], 4),
                'reconciled_by_user_id' => $reconciliationData['reconciled_by_user_id'],
                'reconciled_at' => now(),
                'opening_stock_value_ugx' => round($cogsData['opening_value'] ?? 0, 4),
                'closing_stock_value_ugx' => round($cogsData['closing_value'] ?? 0, 4),
                'inventory_value_change_ugx' => round(($cogsData['closing_value'] ?? 0) - ($cogsData['opening_value'] ?? 0), 4),
                'cost_of_goods_available_ugx' => round($cogsData['cost_of_goods_available'] ?? 0, 4),
                'valuation_method' => $reconciliationData['valuation_method'] ?? 'FIFO',
                'valuation_quality' => $reconciliationData['valuation_quality'] ?? 'COMPLETE',
                'inventory_variance_ugx' => 0.0000,
                'valuation_processed_at' => now(),
                'valuation_processed_by' => $reconciliationData['reconciled_by_user_id'],
                'requires_revaluation' => false,
                'valuation_error_message' => null
            ];

            // Validate against schema
            $this->validateSchemaCompliance('daily_reconciliations', $data);
            $reconciliationId = DB::table('daily_reconciliations')->insertGetId($data);

            // Process FIFO consumption
            $this->processFifoConsumption($reconciliationId, $tankId, $totalDispensed, $cogsData);

            // Check for variance alerts
            $this->checkVarianceAlerts($tankId, $reconciliationId, $data);

            // Create financial ledger entries
            $this->createFinancialLedgerEntries($tankId, $reconciliationId, $data);

            // Update tank current volume
            DB::table('tanks')
                ->where('id', $tankId)
                ->update(['current_volume_liters' => $reconciliationData['actual_closing_stock_liters']]);

            // Log to audit
            $this->logAudit('daily_reconciliations', $reconciliationId, 'INSERT', null, $data, $reconciliationData['reconciled_by_user_id']);

            DB::commit();
            return $reconciliationId;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Daily reconciliation failed", [
                'tank_id' => $tankId,
                'date' => $reconciliationDate->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * MISSING METHOD 3: Calculate opening stock for reconciliation
     */
    private function calculateOpeningStock(int $tankId, Carbon $date): float
    {
        $previousDay = $date->copy()->subDay();

        // Try to get from previous reconciliation
        $previousReconciliation = DB::table('daily_reconciliations')
            ->where('tank_id', $tankId)
            ->where('reconciliation_date', $previousDay->format('Y-m-d'))
            ->first();

        if ($previousReconciliation) {
            return (float) $previousReconciliation->actual_closing_stock_liters;
        }

        // Try to get from daily readings
        $previousReading = DB::table('daily_readings')
            ->where('tank_id', $tankId)
            ->where('reading_date', $previousDay->format('Y-m-d'))
            ->first();

        if ($previousReading) {
            return (float) $previousReading->evening_dip_liters;
        }

        // Fallback to current tank volume
        $tank = DB::table('tanks')->where('id', $tankId)->first();
        return $tank ? (float) $tank->current_volume_liters : 0;
    }

    /**
     * MISSING METHOD 4: Calculate FIFO cost of goods sold
     */
    private function calculateFifoCogs(int $tankId, float $volumeToConsume): array
    {
        if ($volumeToConsume <= 0) {
            return [
                'total_cogs' => 0,
                'consumption_log' => [],
                'opening_value' => 0,
                'closing_value' => 0,
                'cost_of_goods_available' => 0
            ];
        }

        // Get FIFO layers in sequence order
        $fifoLayers = DB::table('fifo_layers')
            ->where('tank_id', $tankId)
            ->where('is_exhausted', false)
            ->where('remaining_volume_liters', '>', 0)
            ->orderBy('layer_sequence')
            ->get();

        $totalCogs = 0;
        $remainingToConsume = $volumeToConsume;
        $consumptionLog = [];
        $openingValue = 0;
        $closingValue = 0;

        // Calculate opening inventory value
        foreach ($fifoLayers as $layer) {
            $openingValue += $layer->remaining_volume_liters * $layer->cost_per_liter_ugx;
        }

        // Process consumption using FIFO
        foreach ($fifoLayers as $layer) {
            if ($remainingToConsume <= 0) {
                // Add remaining layer value to closing
                $closingValue += $layer->remaining_volume_liters * $layer->cost_per_liter_ugx;
                continue;
            }

            $volumeFromThisLayer = min($remainingToConsume, $layer->remaining_volume_liters);
            $costFromThisLayer = $volumeFromThisLayer * $layer->cost_per_liter_ugx;

            $totalCogs += $costFromThisLayer;
            $remainingToConsume -= $volumeFromThisLayer;

            // Log consumption
            $consumptionLog[] = [
                'layer_id' => $layer->id,
                'volume_consumed' => $volumeFromThisLayer,
                'cost_per_liter' => $layer->cost_per_liter_ugx,
                'total_cost' => $costFromThisLayer,
                'remaining_after' => $layer->remaining_volume_liters - $volumeFromThisLayer
            ];

            // Update layer
            $newRemainingVolume = $layer->remaining_volume_liters - $volumeFromThisLayer;
            $isExhausted = $newRemainingVolume <= 0.001;

            DB::table('fifo_layers')
                ->where('id', $layer->id)
                ->update([
                    'remaining_volume_liters' => max(0, $newRemainingVolume),
                    'remaining_value_ugx' => max(0, $newRemainingVolume * $layer->cost_per_liter_ugx),
                    'consumed_value_ugx' => $layer->consumed_value_ugx + $costFromThisLayer,
                    'is_exhausted' => $isExhausted,
                    'layer_status' => $isExhausted ? 'DEPLETED' : 'ACTIVE',
                    'updated_at' => now(),
                    'valuation_last_updated' => now()
                ]);

            // Add remaining value to closing if not exhausted
            if (!$isExhausted) {
                $closingValue += $newRemainingVolume * $layer->cost_per_liter_ugx;
            }
        }

        return [
            'total_cogs' => round($totalCogs, 4),
            'consumption_log' => $consumptionLog,
            'opening_value' => round($openingValue, 4),
            'closing_value' => round($closingValue, 4),
            'cost_of_goods_available' => round($openingValue, 4)
        ];
    }

    /**
     * MISSING METHOD 5: Check for variance alerts and create notifications
     */
    private function checkVarianceAlerts(int $tankId, int $reconciliationId, array $reconciliationData): void
    {
        try {
            // Get tank and station info
            $tank = DB::table('tanks')
                ->join('stations', 'tanks.station_id', '=', 'stations.id')
                ->where('tanks.id', $tankId)
                ->select('tanks.*', 'stations.name as station_name')
                ->first();

            if (!$tank) {
                throw new Exception("Tank not found for variance check");
            }

            // Calculate variance percentage (using generated field logic)
            $theoretical = $reconciliationData['opening_stock_liters'] +
                          $reconciliationData['total_delivered_liters'] -
                          $reconciliationData['total_dispensed_liters'];

            $variance = $reconciliationData['actual_closing_stock_liters'] - $theoretical;
            $variancePercentage = $theoretical > 0 ? ($variance / $theoretical) * 100 : 0;
            $absVariancePercentage = abs($variancePercentage);

            // Determine severity based on thresholds
            $severity = match(true) {
                $absVariancePercentage >= 10.0 => 'critical',
                $absVariancePercentage >= 5.0 => 'high',
                $absVariancePercentage >= 2.0 => 'medium',
                default => 'low'
            };

            // Only create notifications for significant variances
            if ($absVariancePercentage >= 2.0) {
                $title = sprintf(
                    '%s: Volume Variance - Tank %s',
                    $severity === 'critical' ? 'CRITICAL' : 'Volume Variance Alert',
                    $tank->tank_number
                );

                $message = sprintf(
                    '%s variance of %.2f%% (%s%.3fL) %s immediate investigation.',
                    $severity === 'critical' ? 'Critical' : 'Volume',
                    $absVariancePercentage,
                    $variance >= 0 ? '+' : '',
                    $variance,
                    $severity === 'critical' ? 'requires' : 'detected. Variance:'
                );

                $notificationData = [
                    'station_id' => $tank->station_id,
                    'tank_id' => $tankId,
                    'meter_id' => null,
                    'notification_type' => 'volume_variance',
                    'severity' => $severity,
                    'title' => $title,
                    'message' => $message,
                    'variance_magnitude' => round(abs($variance), 3),
                    'variance_percentage' => round($absVariancePercentage, 4),
                    'notification_date' => $reconciliationData['reconciliation_date'],
                    'status' => 'open',
                    'resolution_notes' => null,
                    'created_by_system' => true,
                    'resolved_by_user_id' => null,
                    'resolved_at' => null,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $this->validateSchemaCompliance('notifications', $notificationData);
                $notificationId = DB::table('notifications')->insertGetId($notificationData);

                // Log notification creation
                $this->logAudit('notifications', $notificationId, 'INSERT', null, $notificationData, null);
            }

            // Check for low stock alerts
            $this->checkLowStockAlerts($tankId, $reconciliationData['actual_closing_stock_liters']);

        } catch (Exception $e) {
            Log::error("Variance alert check failed", [
                'tank_id' => $tankId,
                'reconciliation_id' => $reconciliationId,
                'error' => $e->getMessage()
            ]);
            // Don't throw - variance alerts shouldn't break reconciliation
        }
    }

    /**
     * Process FIFO consumption logging
     */
    private function processFifoConsumption(int $reconciliationId, int $tankId, float $totalDispensed, array $cogsData): void
    {
        $sequence = 1;
        foreach ($cogsData['consumption_log'] as $consumption) {
            $logData = [
                'reconciliation_id' => $reconciliationId,
                'fifo_layer_id' => $consumption['layer_id'],
                'volume_consumed_liters' => round($consumption['volume_consumed'], 3),
                'cost_per_liter_ugx' => round($consumption['cost_per_liter'], 4),
                'consumption_sequence' => $sequence++,
                'created_at' => now(),
                'inventory_value_before_ugx' => null,
                'inventory_value_after_ugx' => null,
                'weighted_avg_cost_ugx' => round($consumption['cost_per_liter'], 4),
                'valuation_impact_ugx' => round($consumption['total_cost'], 4)
            ];

            $this->validateSchemaCompliance('fifo_consumption_log', $logData);
            DB::table('fifo_consumption_log')->insert($logData);
        }
    }

    /**
     * Create financial ledger entries for reconciliation
     */
    private function createFinancialLedgerEntries(int $tankId, int $reconciliationId, array $reconciliationData): void
    {
        $tank = DB::table('tanks')->where('id', $tankId)->first();
        $entryDate = $reconciliationData['reconciliation_date'];

        // Revenue entry
        $revenueEntry = [
            'station_id' => $tank->station_id,
            'entry_date' => $entryDate,
            'account_type' => 'revenue',
            'fuel_type' => $tank->fuel_type,
            'debit_amount_ugx' => 0.0000,
            'credit_amount_ugx' => $reconciliationData['total_sales_ugx'],
            'description' => sprintf('Daily fuel sales - Tank %s', $tank->tank_number),
            'reference_table' => 'daily_reconciliations',
            'reference_id' => $reconciliationId,
            'reconciliation_id' => $reconciliationId,
            'created_at' => now()
        ];

        // COGS entry
        $cogsEntry = [
            'station_id' => $tank->station_id,
            'entry_date' => $entryDate,
            'account_type' => 'cogs',
            'fuel_type' => $tank->fuel_type,
            'debit_amount_ugx' => $reconciliationData['total_cogs_ugx'],
            'credit_amount_ugx' => 0.0000,
            'description' => sprintf('Cost of goods sold - Tank %s', $tank->tank_number),
            'reference_table' => 'daily_reconciliations',
            'reference_id' => $reconciliationId,
            'reconciliation_id' => $reconciliationId,
            'created_at' => now()
        ];

        $this->validateSchemaCompliance('financial_ledger', $revenueEntry);
        $this->validateSchemaCompliance('financial_ledger', $cogsEntry);

        DB::table('financial_ledger')->insert([$revenueEntry, $cogsEntry]);
    }

    /**
     * Check low stock alerts
     */
    private function checkLowStockAlerts(int $tankId, float $currentVolume): void
    {
        $tank = DB::table('tanks')
            ->join('stock_alert_thresholds', 'tanks.id', '=', 'stock_alert_thresholds.tank_id')
            ->join('stations', 'tanks.station_id', '=', 'stations.id')
            ->where('tanks.id', $tankId)
            ->where('stock_alert_thresholds.is_active', true)
            ->select('tanks.*', 'stock_alert_thresholds.*', 'stations.name as station_name')
            ->first();

        if (!$tank) return;

        $fillPercentage = ($currentVolume / $tank->capacity_liters) * 100;
        $shouldAlert = false;
        $severity = 'low';

        if ($fillPercentage <= $tank->critical_stock_percentage) {
            $shouldAlert = true;
            $severity = 'critical';
        } elseif ($fillPercentage <= $tank->low_stock_percentage) {
            $shouldAlert = true;
            $severity = 'high';
        }

        if ($shouldAlert) {
            $title = sprintf('CRITICAL: Low Stock - Tank %s', $tank->tank_number);
            $message = sprintf(
                'Tank %s is at %.1f%% capacity (%.0fL). Immediate refill required.',
                $tank->tank_number,
                $fillPercentage,
                $currentVolume
            );

            $notificationData = [
                'station_id' => $tank->station_id,
                'tank_id' => $tankId,
                'meter_id' => null,
                'notification_type' => 'low_stock',
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'variance_magnitude' => null,
                'variance_percentage' => null,
                'notification_date' => now()->format('Y-m-d'),
                'status' => 'open',
                'resolution_notes' => null,
                'created_by_system' => true,
                'resolved_by_user_id' => null,
                'resolved_at' => null,
                'created_at' => now(),
                'updated_at' => now()
            ];

            $this->validateSchemaCompliance('notifications', $notificationData);
            DB::table('notifications')->insert($notificationData);
        }
    }
}
