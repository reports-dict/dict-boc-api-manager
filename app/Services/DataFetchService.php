<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataFetchService
{
    public function fetch(string $type, Carbon $from, Carbon $to, array $containers = []): array
    {
        return match($type) {
            'discharge' => $this->fetchDischarge($from, $to, $containers),
            'load'      => $this->fetchLoad($from, $to, $containers),
            'release'   => $this->fetchRelease($from, $to, $containers),
            'receive'   => $this->fetchReceive($from, $to, $containers),
        };
    }

    private function containerClause(array $containers): array
    {
        if (empty($containers)) {
            return ['', []];
        }
        $placeholders = implode(', ', array_fill(0, count($containers), '?'));
        return ["\n                AND unit.id IN ({$placeholders})", $containers];
    }

    public function fetchDischarge(Carbon $from, Carbon $to, array $containers = []): array
    {
        [$containerClause, $containerBindings] = $this->containerClause($containers);

        $sql = "
            SELECT
                routing_point.terminal AS port,
                bizunit.name AS shipping_line,
                vvsl.name AS vessel_name,
                vvsl_vd.ib_vyg AS voyage_no,
                unit.id AS container_no,
                RIGHT(eq_type.basic_length, 2) AS container_size,
                CASE
                    WHEN unit.freight_kind = 'MTY' THEN 'EMPTY'
                    WHEN unit.freight_kind = 'FCL' THEN 'FULL'
                    ELSE unit.freight_kind
                END AS container_load,
                bizunit.name AS container_owner,
                vvsl.service_registry_nbr AS registry_no,
                unit.flex_string04 AS bl_no,
                NULL AS master_bl,
                CAST(fcy_visit.time_rnd AS DATE) AS discharged_date,
                ref_biz_consignee.name AS consignee,
                NULL AS notify_party,
                cmdy.description AS description,
                unit.goods_and_ctr_wt_kg AS gross_weight,
                ref_eq.tare_kg AS tare_weight,
                NULL AS declared_weight,
                NULL AS manifest_weight
            FROM [sparcsn4].[dbo].[inv_unit] AS unit
            INNER JOIN [sparcsn4].[dbo].[inv_unit_fcy_visit] AS fcy_visit ON unit.gkey = fcy_visit.unit_gkey
            INNER JOIN [sparcsn4].[dbo].[ref_routing_point] AS routing_point ON unit.pod1_gkey = routing_point.gkey
            INNER JOIN [sparcsn4].[dbo].[inv_goods] AS goods ON unit.goods = goods.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS ref_biz ON goods.shipper_bzu = ref_biz.gkey
            INNER JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS bizunit ON unit.line_op = bizunit.gkey
            INNER JOIN [sparcsn4].[dbo].[argo_carrier_visit] AS argo_cv ON unit.declrd_ib_cv = argo_cv.gkey
            LEFT  JOIN dbo.vsl_vessel_visit_details AS vvsl_vd ON vvsl_vd.vvd_gkey = argo_cv.cvcvd_gkey
            LEFT  JOIN dbo.vsl_vessels AS vvsl ON vvsl.gkey = vvsl_vd.vessel_gkey
            INNER JOIN [sparcsn4].[dbo].[ref_equipment] AS ref_eq ON unit.eq_gkey = ref_eq.gkey
            INNER JOIN [sparcsn4].[dbo].[ref_equip_type] AS eq_type ON ref_eq.eqtyp_gkey = eq_type.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS ref_biz_consignee ON goods.consignee_bzu = ref_biz_consignee.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_commodity] AS cmdy ON goods.commodity_gkey = cmdy.gkey
            WHERE
                fcy_visit.time_rnd >= ?
                AND fcy_visit.time_rnd < ?
                AND fcy_visit.transit_state NOT IN ('S10_ADVISED','S20_INBOUND','S99_RETIRED')
                AND unit.category IN ('IMPRT', 'TRSHP'){$containerClause}
            ORDER BY fcy_visit.time_rnd
        ";

        return DB::connection('sqlsrv')->select($sql, array_merge(
            [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')],
            $containerBindings
        ));
    }

    public function fetchLoad(Carbon $from, Carbon $to, array $containers = []): array
    {
        [$containerClause, $containerBindings] = $this->containerClause($containers);

        $sql = "
            SELECT
                routing_point.terminal AS port,
                bizunit.name AS shipping_line,
                vvsl.name AS vessel_name,
                unit.id AS container_no,
                RIGHT(eq_type.basic_length, 2) AS container_size,
                CASE
                    WHEN unit.freight_kind = 'MTY' THEN 'EMPTY'
                    WHEN unit.freight_kind = 'FCL' THEN 'FULL'
                    ELSE unit.freight_kind
                END AS container_load,
                bizunit.name AS container_owner,
                vvsl.service_registry_nbr AS registry_no,
                CAST(fcy_visit.time_load AS DATE) AS loading_date,
                CAST(fcy_visit.time_load AS TIME(0)) AS loading_time,
                ref_biz.name AS exporter,
                cmdy.description AS description
            FROM [sparcsn4].[dbo].[inv_unit] AS unit
            INNER JOIN [sparcsn4].[dbo].[inv_unit_fcy_visit] AS fcy_visit ON unit.gkey = fcy_visit.unit_gkey
            INNER JOIN [sparcsn4].[dbo].[ref_routing_point] AS routing_point ON unit.pol_gkey = routing_point.gkey
            INNER JOIN [sparcsn4].[dbo].[inv_goods] AS goods ON unit.goods = goods.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS ref_biz ON goods.shipper_bzu = ref_biz.gkey
            INNER JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS bizunit ON unit.line_op = bizunit.gkey
            INNER JOIN [sparcsn4].[dbo].[argo_carrier_visit] AS argo_cv ON unit.cv_gkey = argo_cv.gkey
            LEFT  JOIN dbo.vsl_vessel_visit_details AS vvsl_vd ON vvsl_vd.vvd_gkey = argo_cv.cvcvd_gkey
            LEFT  JOIN dbo.vsl_vessels AS vvsl ON vvsl.gkey = vvsl_vd.vessel_gkey
            INNER JOIN [sparcsn4].[dbo].[ref_equipment] AS ref_eq ON unit.eq_gkey = ref_eq.gkey
            INNER JOIN [sparcsn4].[dbo].[ref_equip_type] AS eq_type ON ref_eq.eqtyp_gkey = eq_type.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_commodity] AS cmdy ON goods.commodity_gkey = cmdy.gkey
            WHERE
                fcy_visit.time_load >= ?
                AND fcy_visit.time_load < ?
                AND fcy_visit.transit_state IN ('S60_LOADED', 'S70_DEPARTED')
                AND unit.category IN ('EXPRT', 'TRSHP'){$containerClause}
            ORDER BY fcy_visit.time_load
        ";

        return DB::connection('sqlsrv')->select($sql, array_merge(
            [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')],
            $containerBindings
        ));
    }

    public function fetchRelease(Carbon $from, Carbon $to, array $containers = []): array
    {
        [$containerClause, $containerBindings] = $this->containerClause($containers);

        $sql = "
            SELECT
                routing_point.terminal AS port,
                bizunit.name AS shipping_line,
                vvsl.name AS vessel_name,
                unit.id AS container_no,
                RIGHT(eq_type.basic_length, 2) AS container_size,
                CASE
                    WHEN unit.freight_kind = 'MTY' THEN 'EMPTY'
                    WHEN unit.freight_kind = 'FCL' THEN 'FULL'
                    ELSE unit.freight_kind
                END AS container_load,
                vvsl.service_registry_nbr AS registry_no,
                unit.flex_string04 AS bl_no,
                NULL AS master_bl,
                CAST(fcy_visit.time_rnd AS DATE) AS discharged_date,
                ref_biz_consignee.name AS consignee,
                NULL AS notify_party,
                cmdy.description AS description,
                CAST(fcy_visit.flex_date02 AS DATE) AS date_issue,
                CAST(fcy_visit.flex_date02 AS TIME(0)) AS time_issue,
                truck.id AS plate_no,
                CAST(fcy_visit.time_out AS DATE) AS date_departure,
                CAST(fcy_visit.time_out AS TIME(0)) AS time_departure
            FROM [sparcsn4].[dbo].[inv_unit] AS unit
            INNER JOIN [sparcsn4].[dbo].[inv_unit_fcy_visit] AS fcy_visit ON unit.gkey = fcy_visit.unit_gkey
            INNER JOIN [sparcsn4].[dbo].[ref_routing_point] AS routing_point ON unit.pod1_gkey = routing_point.gkey
            INNER JOIN [sparcsn4].[dbo].[inv_goods] AS goods ON unit.goods = goods.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS ref_biz ON goods.shipper_bzu = ref_biz.gkey
            INNER JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS bizunit ON unit.line_op = bizunit.gkey
            LEFT  JOIN [sparcsn4].[dbo].[argo_carrier_visit] AS truck ON fcy_visit.actual_ob_cv = truck.gkey
            INNER JOIN [sparcsn4].[dbo].[argo_carrier_visit] AS argo_cv ON unit.declrd_ib_cv = argo_cv.gkey
            LEFT  JOIN dbo.vsl_vessel_visit_details AS vvsl_vd ON vvsl_vd.vvd_gkey = argo_cv.cvcvd_gkey
            LEFT  JOIN dbo.vsl_vessels AS vvsl ON vvsl.gkey = vvsl_vd.vessel_gkey
            INNER JOIN [sparcsn4].[dbo].[ref_equipment] AS ref_eq ON unit.eq_gkey = ref_eq.gkey
            INNER JOIN [sparcsn4].[dbo].[ref_equip_type] AS eq_type ON ref_eq.eqtyp_gkey = eq_type.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS ref_biz_consignee ON goods.consignee_bzu = ref_biz_consignee.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_commodity] AS cmdy ON goods.commodity_gkey = cmdy.gkey
            WHERE
                fcy_visit.flex_date02 >= ?
                AND fcy_visit.flex_date02 < ?
                AND unit.category = 'IMPRT'
                AND fcy_visit.transit_state = 'S70_DEPARTED'{$containerClause}
            ORDER BY fcy_visit.flex_date02
        ";

        return DB::connection('sqlsrv')->select($sql, array_merge(
            [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')],
            $containerBindings
        ));
    }

    public function fetchReceive(Carbon $from, Carbon $to, array $containers = []): array
    {
        [$containerClause, $containerBindings] = $this->containerClause($containers);

        $sql = "
            SELECT
                routing_point.terminal AS port,
                bizunit.name AS shipping_line,
                vvsl.name AS vessel_name,
                unit.id AS container_no,
                RIGHT(eq_type.basic_length, 2) AS container_size,
                CASE
                    WHEN unit.freight_kind = 'MTY' THEN 'EMPTY'
                    WHEN unit.freight_kind = 'FCL' THEN 'FULL'
                    ELSE unit.freight_kind
                END AS container_load,
                vvsl.service_registry_nbr AS registry_no,
                CAST(fcy_visit.flex_date02 AS DATE) AS gate_pass_date,
                CAST(fcy_visit.flex_date02 AS TIME(0)) AS gate_pass_time,
                truck.id AS plate_no,
                CAST(fcy_visit.time_in AS DATE) AS date_receive,
                CAST(fcy_visit.time_in AS TIME(0)) AS time_receive,
                ref_biz.name AS exporter,
                cmdy.description AS description
            FROM [sparcsn4].[dbo].[inv_unit] AS unit
            INNER JOIN [sparcsn4].[dbo].[inv_unit_fcy_visit] AS fcy_visit ON unit.gkey = fcy_visit.unit_gkey
            INNER JOIN [sparcsn4].[dbo].[ref_routing_point] AS routing_point ON unit.pol_gkey = routing_point.gkey
            INNER JOIN [sparcsn4].[dbo].[inv_goods] AS goods ON unit.goods = goods.gkey
            INNER JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS bizunit ON unit.line_op = bizunit.gkey
            LEFT  JOIN [sparcsn4].[dbo].[argo_carrier_visit] AS truck ON fcy_visit.actual_ib_cv = truck.gkey
            INNER JOIN [sparcsn4].[dbo].[argo_carrier_visit] AS argo_cv ON unit.cv_gkey = argo_cv.gkey
            LEFT  JOIN dbo.vsl_vessel_visit_details AS vvsl_vd ON vvsl_vd.vvd_gkey = argo_cv.cvcvd_gkey
            LEFT  JOIN dbo.vsl_vessels AS vvsl ON vvsl.gkey = vvsl_vd.vessel_gkey
            INNER JOIN [sparcsn4].[dbo].[ref_equipment] AS ref_eq ON unit.eq_gkey = ref_eq.gkey
            INNER JOIN [sparcsn4].[dbo].[ref_equip_type] AS eq_type ON ref_eq.eqtyp_gkey = eq_type.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] AS ref_biz ON goods.shipper_bzu = ref_biz.gkey
            LEFT  JOIN [sparcsn4].[dbo].[ref_commodity] AS cmdy ON goods.commodity_gkey = cmdy.gkey
            WHERE
                fcy_visit.flex_date02 >= ?
                AND fcy_visit.flex_date02 < ?
                AND unit.category = 'EXPRT'
                AND fcy_visit.transit_state IN ('S40_YARD','S50_ECOUT','S60_LOADED'){$containerClause}
            ORDER BY fcy_visit.flex_date02
        ";

        return DB::connection('sqlsrv')->select($sql, array_merge(
            [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')],
            $containerBindings
        ));
    }
}
