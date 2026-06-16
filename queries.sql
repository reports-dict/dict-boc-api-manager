--DISCHARGING
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
 INNER JOIN [sparcsn4].[dbo].[inv_unit_fcy_visit] AS fcy_visit 
 ON unit.gkey = fcy_visit.unit_gkey 
INNER JOIN [sparcsn4].[dbo].[ref_routing_point] as routing_point 
 ON unit.pod1_gkey = routing_point.gkey 
INNER JOIN [sparcsn4].[dbo].[inv_goods] as goods 
 ON unit.goods=goods.gkey 
LEFT JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as ref_biz 
 ON goods.shipper_bzu=ref_biz.gkey 
INNER JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as bizunit 
 ON unit.line_op = bizunit.gkey 
INNER JOIN [sparcsn4].[dbo].[argo_carrier_visit] as argo_cv 
 ON unit.declrd_ib_cv = argo_cv.gkey
LEFT JOIN dbo.vsl_vessel_visit_details as vvsl_vd
 ON vvsl_vd.vvd_gkey=argo_cv.cvcvd_gkey
LEFT JOIN dbo.vsl_vessels as vvsl
 ON vvsl.gkey=vvsl_vd.vessel_gkey
INNER JOIN [sparcsn4].[dbo].[ref_equipment] as ref_eq
 ON unit.eq_gkey = ref_eq.gkey
INNER JOIN [sparcsn4].[dbo].[ref_equip_type] as eq_type
 ON ref_eq.eqtyp_gkey = eq_type.gkey
LEFT JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as ref_biz_consignee
 ON goods.consignee_bzu=ref_biz_consignee.gkey
LEFT JOIN [sparcsn4].[dbo].[ref_commodity] as cmdy
 ON goods.commodity_gkey = cmdy.gkey
WHERE 
 	fcy_visit.time_rnd >= DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()) - 1, 0) 
 	AND fcy_visit.time_rnd < DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()), 0) 
	AND fcy_visit.transit_state NOT IN ('S10_ADVISED','S20_INBOUND','S99_RETIRED')
	AND (
        unit.category IN ('IMPRT', 'TRSHP')
        OR (
            unit.category = 'THRGH'
            AND fcy_visit.restow_typ = 'RESTOW'
        )
    )
 ORDER BY fcy_visit.time_rnd




--RECEIVING
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
INNER JOIN [sparcsn4].[dbo].[inv_unit_fcy_visit] AS fcy_visit 
 ON unit.gkey = fcy_visit.unit_gkey 
INNER JOIN [sparcsn4].[dbo].[ref_routing_point] as routing_point 
 ON unit.pol_gkey = routing_point.gkey
INNER JOIN [sparcsn4].[dbo].[inv_goods] as goods
 ON unit.goods=goods.gkey
INNER JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as bizunit 
 ON unit.line_op = bizunit.gkey
LEFT JOIN [sparcsn4].[dbo].[argo_carrier_visit] as truck 
 ON fcy_visit.actual_ib_cv = truck.gkey
INNER JOIN [sparcsn4].[dbo].[argo_carrier_visit] as argo_cv 
 ON unit.cv_gkey = argo_cv.gkey
LEFT JOIN dbo.vsl_vessel_visit_details as vvsl_vd
 ON vvsl_vd.vvd_gkey=argo_cv.cvcvd_gkey
LEFT JOIN dbo.vsl_vessels as vvsl
 ON vvsl.gkey=vvsl_vd.vessel_gkey
INNER JOIN [sparcsn4].[dbo].[ref_equipment] as ref_eq
 ON unit.eq_gkey = ref_eq.gkey
 
 INNER JOIN [sparcsn4].[dbo].[ref_equip_type] as eq_type
 ON ref_eq.eqtyp_gkey = eq_type.gkey
LEFT JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as ref_biz
 ON goods.shipper_bzu=ref_biz.gkey
LEFT JOIN [sparcsn4].[dbo].[ref_commodity] as cmdy
 ON goods.commodity_gkey = cmdy.gkey
WHERE 
 	fcy_visit.flex_date02 >= DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()) - 1, 0) 
 	AND fcy_visit.flex_date02 < DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()), 0) 
 	AND unit.category = 'EXPRT'
	AND fcy_visit.transit_state IN ('S40_YARD','S50_ECOUT','S60_LOADED')
ORDER BY fcy_visit.flex_date02






--RELEASING
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
 INNER JOIN [sparcsn4].[dbo].[inv_unit_fcy_visit] AS fcy_visit 
 ON unit.gkey = fcy_visit.unit_gkey 
INNER JOIN [sparcsn4].[dbo].[ref_routing_point] as routing_point 
 ON unit.pod1_gkey = routing_point.gkey 
INNER JOIN [sparcsn4].[dbo].[inv_goods] as goods 
 ON unit.goods=goods.gkey 
LEFT JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as ref_biz 
 ON goods.shipper_bzu=ref_biz.gkey 
INNER JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as bizunit 
 ON unit.line_op = bizunit.gkey
LEFT JOIN [sparcsn4].[dbo].[argo_carrier_visit] as truck 
 ON fcy_visit.actual_ob_cv = truck.gkey
INNER JOIN [sparcsn4].[dbo].[argo_carrier_visit] as argo_cv 
 ON unit.declrd_ib_cv = argo_cv.gkey
LEFT JOIN dbo.vsl_vessel_visit_details as vvsl_vd
 ON vvsl_vd.vvd_gkey=argo_cv.cvcvd_gkey
LEFT JOIN dbo.vsl_vessels as vvsl
 ON vvsl.gkey=vvsl_vd.vessel_gkey
INNER JOIN [sparcsn4].[dbo].[ref_equipment] as ref_eq
 ON unit.eq_gkey = ref_eq.gkey
INNER JOIN [sparcsn4].[dbo].[ref_equip_type] as eq_type
 ON ref_eq.eqtyp_gkey = eq_type.gkey
LEFT JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as ref_biz_consignee
 ON goods.consignee_bzu=ref_biz_consignee.gkey
LEFT JOIN [sparcsn4].[dbo].[ref_commodity] as cmdy
 ON goods.commodity_gkey = cmdy.gkey
WHERE 
 	fcy_visit.flex_date02 >= DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()) - 1, 0) 
 	AND fcy_visit.flex_date02 < DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()), 0) 
 	AND unit.category = 'IMPRT'
	AND fcy_visit.transit_state = 'S70_DEPARTED'
 ORDER BY fcy_visit.flex_date02





--LOADING
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
 INNER JOIN [sparcsn4].[dbo].[inv_unit_fcy_visit] AS fcy_visit 
 ON unit.gkey = fcy_visit.unit_gkey 
INNER JOIN [sparcsn4].[dbo].[ref_routing_point] as routing_point 
 ON unit.pol_gkey = routing_point.gkey 
INNER JOIN [sparcsn4].[dbo].[inv_goods] as goods 
 ON unit.goods=goods.gkey 
LEFT JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as ref_biz 
 ON goods.shipper_bzu=ref_biz.gkey 
INNER JOIN [sparcsn4].[dbo].[ref_bizunit_scoped] as bizunit 
 ON unit.line_op = bizunit.gkey 
INNER JOIN [sparcsn4].[dbo].[argo_carrier_visit] as argo_cv 
 ON unit.cv_gkey = argo_cv.gkey
LEFT JOIN dbo.vsl_vessel_visit_details as vvsl_vd
 ON vvsl_vd.vvd_gkey=argo_cv.cvcvd_gkey
LEFT JOIN dbo.vsl_vessels as vvsl
 ON vvsl.gkey=vvsl_vd.vessel_gkey
INNER JOIN [sparcsn4].[dbo].[ref_equipment] as ref_eq
 ON unit.eq_gkey = ref_eq.gkey
INNER JOIN [sparcsn4].[dbo].[ref_equip_type] as eq_type
 ON ref_eq.eqtyp_gkey = eq_type.gkey
LEFT JOIN [sparcsn4].[dbo].[ref_commodity] as cmdy
 ON goods.commodity_gkey = cmdy.gkey
WHERE 
 	fcy_visit.time_load >= DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()) - 1, 0)
    AND fcy_visit.time_load < DATEADD(DAY, DATEDIFF(DAY, 0, GETDATE()), 0)
    AND fcy_visit.transit_state IN ('S60_LOADED', 'S70_DEPARTED')
    AND (
        unit.category IN ('EXPRT', 'TRSHP')
        OR (
            unit.category = 'THRGH'
            AND fcy_visit.restow_typ = 'RESTOW'
        )
    )
ORDER BY fcy_visit.time_load
