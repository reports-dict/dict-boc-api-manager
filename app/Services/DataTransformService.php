<?php

namespace App\Services;

class DataTransformService
{
    public function transform(string $type, array $rows): array
    {
        return match($type) {
            'discharge' => $this->transformDischarge($rows),
            'load'      => $this->transformLoad($rows),
            'release'   => $this->transformRelease($rows),
            'receive'   => $this->transformReceive($rows),
        };
    }

    public function transformDischarge(array $rows): array
    {
        return array_map(function ($row) {
            $row = (array) $row;
            return [
                'port'             => $row['port'],
                'shipping_line'    => $row['shipping_line'],
                'vessel_name'      => $row['vessel_name'],
                'voyage_no'        => $row['voyage_no'],
                'container_no'     => $row['container_no'],
                'container_size'   => (int) $row['container_size'],
                'container_load'   => $row['container_load'],
                'container_owner'  => $row['container_owner'],
                'registry_no'      => $row['registry_no'],
                'bl_no'            => $row['bl_no'],
                'master_bl'        => $row['master_bl'],
                'discharge_date'   => $row['discharged_date'],
                'consignee'        => $row['consignee'],
                'notify_party'     => $row['notify_party'],
                'description'      => $row['description'],
                'gross_weight'     => $row['gross_weight'] !== null ? (float) $row['gross_weight'] : null,
                'tare_weight'      => $row['tare_weight'] !== null ? (float) $row['tare_weight'] : null,
                'declared_weight'  => $row['declared_weight'] !== null ? (float) $row['declared_weight'] : null,
                'manifest_weight'  => $row['manifest_weight'] !== null ? (float) $row['manifest_weight'] : null,
            ];
        }, $rows);
    }

    public function transformLoad(array $rows): array
    {
        return array_map(function ($row) {
            $row = (array) $row;
            return [
                'port'            => $row['port'],
                'shipping_line'   => $row['shipping_line'],
                'vessel_name'     => $row['vessel_name'],
                'container_no'    => $row['container_no'],
                'container_size'  => (int) $row['container_size'],
                'container_load'  => $row['container_load'],
                'registry_no'     => $row['registry_no'],
                'loading_date'    => $row['loading_date'],
                'loading_time'    => $row['loading_time'],
                'exporter'        => $row['exporter'],
                'description'     => $row['description'],
            ];
        }, $rows);
    }

    public function transformRelease(array $rows): array
    {
        return array_map(function ($row) {
            $row = (array) $row;
            return [
                'port'            => $row['port'],
                'shipping_line'   => $row['shipping_line'],
                'vessel_name'     => $row['vessel_name'],
                'container_no'    => $row['container_no'],
                'container_size'  => (int) $row['container_size'],
                'container_load'  => $row['container_load'],
                'registry_no'     => $row['registry_no'],
                'bl_no'           => $row['bl_no'],
                'master_bl'       => $row['master_bl'],
                'discharge_date'  => $row['discharged_date'],
                'consignee'       => $row['consignee'],
                'notify_party'    => $row['notify_party'],
                'description'     => $row['description'],
                'date_issue'      => $row['date_issue'],
                'time_issue'      => $row['time_issue'],
                'plate_no'        => $row['plate_no'],
                'date_departure'  => $row['date_departure'],
                'time_departure'  => $row['time_departure'],
            ];
        }, $rows);
    }

    public function transformReceive(array $rows): array
    {
        return array_map(function ($row) {
            $row = (array) $row;
            return [
                'port'            => $row['port'],
                'shipping_line'   => $row['shipping_line'],
                'vessel_name'     => $row['vessel_name'],
                'container_no'    => $row['container_no'],
                'container_size'  => (int) $row['container_size'],
                'container_load'  => $row['container_load'],
                'registry_no'     => $row['registry_no'],
                'gate_pass_date'  => $row['gate_pass_date'],
                'gate_pass_time'  => $row['gate_pass_time'],
                'plate_no'        => $row['plate_no'],
                'date_receive'    => $row['date_receive'],
                'time_receive'    => $row['time_receive'],
                'exporter'        => $row['exporter'],
                'description'     => $row['description'],
            ];
        }, $rows);
    }
}
