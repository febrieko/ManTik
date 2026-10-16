<?php

namespace App\Services;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Log;

class MikrotikService
{
    protected ?Client $client = null;
    protected Router $router;
    public string $lastError = '';

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    protected function getIdKey(): string
    {
        return $this->router->ros_version === 'v6' ? 'numbers' : '.id';
    }

    /**
     * Connect to MikroTik RouterOS
     */
    public function connect(): bool
    {
        $primaryLegacy = ($this->router->ros_version === 'v6');
        $timeout = (int) config('mikrotik.timeout', 15);

        try {
            $this->client = new Client([
                'host'     => $this->router->host,
                'user'     => $this->router->username,
                'pass'     => $this->router->password,
                'port'     => (int) $this->router->port,
                'timeout'  => $timeout,
                'legacy'   => $primaryLegacy,
            ]);
            return true;
        } catch (Exception $e) {
            // Coba mode legacy alternatif jika percobaan pertama gagal (misal jika RouterOS v6/v7 handshake berbeda)
            try {
                $this->client = new Client([
                    'host'     => $this->router->host,
                    'user'     => $this->router->username,
                    'pass'     => $this->router->password,
                    'port'     => (int) $this->router->port,
                    'timeout'  => $timeout,
                    'legacy'   => !$primaryLegacy,
                ]);
                return true;
            } catch (Exception $e2) {
                $this->lastError = $e2->getMessage();
                Log::error("MikroTik connection failed [{$this->router->name}]: primary (" . ($primaryLegacy ? 'legacy' : 'v7') . "): {$e->getMessage()} | fallback: {$e2->getMessage()}");
                return false;
            }
        }
    }

    /**
     * Execute a RouterOS query
     */
    public function query(string $command, array $params = []): array
    {
        if (!$this->client) {
            if (!$this->connect()) {
                throw new Exception("Gagal terhubung ke router: " . $this->lastError);
            }
        }

        $query = new Query($command);
        foreach ($params as $key => $value) {
            if (str_starts_with($key, '?')) {
                $query->where(substr($key, 1), $value);
            } else {
                $query->equal($key, $value);
            }
        }

        try {
            return $this->client->query($query)->read();
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================
    // SYSTEM INFO
    // =========================================

    public function getSystemResource(): array
    {
        $data = $this->query('/system/resource/print');
        return $data[0] ?? [];
    }

    public function getSystemIdentity(): array
    {
        $data = $this->query('/system/identity/print');
        return $data[0] ?? [];
    }

    public function getSystemRouterboard(): array
    {
        $data = $this->query('/system/routerboard/print');
        return $data[0] ?? [];
    }

    // =========================================
    // PPPoE MANAGEMENT
    // =========================================

    public function getPppoeUsers(): array
    {
        return $this->query('/ppp/secret/print');
    }

    public function getPppoeActiveSessions(): array
    {
        return $this->query('/ppp/active/print');
    }

    public function addPppoeUser(array $data): array
    {
        $params = [
            'name'           => $data['username'] ?? $data['name'] ?? '',
            'password'       => $data['password'] ?? '',
            'service'        => $data['service'] ?? 'pppoe',
            'profile'        => $data['profile'] ?? 'default',
            'disabled'       => (isset($data['is_disabled']) && $data['is_disabled']) ? 'yes' : 'no',
        ];

        if (!empty($data['local_address'])) $params['local-address'] = $data['local_address'];
        if (!empty($data['remote_address'])) $params['remote-address'] = $data['remote_address'];
        if (!empty($data['comment'])) $params['comment'] = $data['comment'];

        return $this->query('/ppp/secret/add', $params);
    }

    public function editPppoeUser(string $mikrotikId, array $data): array
    {
        $params = [];
        if (isset($data['password'])) $params['=password'] = $data['password'];
        if (isset($data['profile'])) $params['=profile'] = $data['profile'];
        if (isset($data['comment'])) $params['=comment'] = $data['comment'];
        if (isset($data['remote_address'])) $params['=remote-address'] = $data['remote_address'];

        $query = new Query('/ppp/secret/set');
        foreach ($params as $k => $v) {
            $query->equal(ltrim($k, '='), $v);
        }
        $identifier = $this->getIdKey();
        $query->equal($identifier, $mikrotikId);
        return $this->client->query($query)->read();
    }

    public function deletePppoeUser(string $mikrotikId): array
    {
        $query = new Query('/ppp/secret/remove');
        $identifier = $this->getIdKey();
        $query->equal($identifier, $mikrotikId);
        return $this->client->query($query)->read();
    }

    public function enablePppoeUser(string $mikrotikId): array
    {
        $query = new Query('/ppp/secret/set');
        $identifier = $this->getIdKey();
        $query->equal($identifier, $mikrotikId);
        $query->equal('disabled', 'no');
        return $this->client->query($query)->read();
    }

    public function disablePppoeUser(string $mikrotikId): array
    {
        $query = new Query('/ppp/secret/set');
        $identifier = $this->getIdKey();
        $query->equal($identifier, $mikrotikId);
        $query->equal('disabled', 'yes');
        return $this->client->query($query)->read();
    }

    // =========================================
    // HOTSPOT MANAGEMENT
    // =========================================

    public function getHotspotUsers(): array
    {
        return $this->query('/ip/hotspot/user/print');
    }

    public function getHotspotActiveSessions(): array
    {
        return $this->query('/ip/hotspot/active/print');
    }

    public function addHotspotUser(array $data): array
    {
        $params = [
            'name'      => $data['username'] ?? $data['name'] ?? '',
            'password'  => $data['password'] ?? '',
            'profile'   => $data['profile'] ?? 'default',
            'server'    => $data['server'] ?? 'all',
            'disabled'  => (isset($data['disabled']) ? $data['disabled'] : ((isset($data['is_disabled']) && $data['is_disabled']) ? 'yes' : 'no')),
        ];

        if (!empty($data['comment'])) $params['comment'] = $data['comment'];
        if (!empty($data['limit-uptime']) || !empty($data['limit_uptime'])) $params['limit-uptime'] = $data['limit-uptime'] ?? $data['limit_uptime'];
        if (!empty($data['limit-bytes-total']) || !empty($data['limit_bytes_total'])) $params['limit-bytes-total'] = $data['limit-bytes-total'] ?? $data['limit_bytes_total'];

        return $this->query('/ip/hotspot/user/add', $params);
    }

    public function deleteHotspotUser(string $mikrotikId): array
    {
        $query = new Query('/ip/hotspot/user/remove');
        $query->equal($this->getIdKey(), $mikrotikId);
        return $this->client->query($query)->read();
    }

    public function deleteHotspotUserByName(string $username): void
    {
        $users = $this->query('/ip/hotspot/user/print');
        $mikrotikId = null;
        foreach ($users as $u) {
            if (($u['name'] ?? '') === $username && isset($u['.id'])) {
                $mikrotikId = $u['.id'];
                break;
            }
        }
        if ($mikrotikId) {
            $this->deleteHotspotUser($mikrotikId);
        }
    }

    public function editHotspotUserByName(string $username, array $data): void
    {
        $users = $this->query('/ip/hotspot/user/print');
        $mikrotikId = null;
        foreach ($users as $u) {
            if (($u['name'] ?? '') === $username && isset($u['.id'])) {
                $mikrotikId = $u['.id'];
                break;
            }
        }
        if ($mikrotikId) {
            $params = [];
            if (isset($data['password'])) $params['=password'] = $data['password'];
            if (isset($data['profile'])) $params['=profile'] = $data['profile'];
            if (isset($data['comment'])) $params['=comment'] = $data['comment'];
            if (isset($data['limit-uptime'])) $params['=limit-uptime'] = $data['limit-uptime'];
            if (isset($data['limit-bytes-total'])) $params['=limit-bytes-total'] = $data['limit-bytes-total'];

            $query = new Query('/ip/hotspot/user/set');
            foreach ($params as $k => $v) {
                $query->equal(ltrim($k, '='), $v);
            }
            $query->equal($this->getIdKey(), $mikrotikId);
            $this->client->query($query)->read();
        }
    }

    public function enableHotspotUserByName(string $username): void
    {
        $users = $this->query('/ip/hotspot/user/print');
        $mikrotikId = null;
        foreach ($users as $u) {
            if (($u['name'] ?? '') === $username && isset($u['.id'])) {
                $mikrotikId = $u['.id'];
                break;
            }
        }
        if ($mikrotikId) {
            $query = new Query('/ip/hotspot/user/enable');
            $query->equal($this->getIdKey(), $mikrotikId);
            $this->client->query($query)->read();
        }
    }

    public function disableHotspotUserByName(string $username): void
    {
        $users = $this->query('/ip/hotspot/user/print');
        $mikrotikId = null;
        foreach ($users as $u) {
            if (($u['name'] ?? '') === $username && isset($u['.id'])) {
                $mikrotikId = $u['.id'];
                break;
            }
        }
        if ($mikrotikId) {
            $query = new Query('/ip/hotspot/user/disable');
            $query->equal($this->getIdKey(), $mikrotikId);
            $this->client->query($query)->read();
        }
    }

    // =========================================
    // QUEUE MANAGEMENT
    // =========================================

    public function getSimpleQueues(): array
    {
        return $this->query('/queue/simple/print');
    }

    public function addSimpleQueue(array $data): array
    {
        $targetKey = $this->router->ros_version === 'v6' ? 'target-addresses' : 'target';
        return $this->query('/queue/simple/add', [
            'name'      => $data['name'],
            $targetKey  => $data['target'],
            'max-limit' => $data['max_limit'] ?? '10M/10M',
            'comment'   => $data['comment'] ?? '',
            'disabled'  => $data['is_disabled'] ? 'yes' : 'no',
        ]);
    }

    public function editSimpleQueue(string $mikrotikId, array $data): array
    {
        $query = new Query('/queue/simple/set');
        $identifier = $this->getIdKey();
        $query->equal($identifier, $mikrotikId);
        if (isset($data['max_limit'])) $query->equal('max-limit', $data['max_limit']);
        if (isset($data['name'])) $query->equal('name', $data['name']);
        if (isset($data['target'])) {
            $targetKey = $this->router->ros_version === 'v6' ? 'target-addresses' : 'target';
            $query->equal($targetKey, $data['target']);
        }
        return $this->client->query($query)->read();
    }

    public function deleteSimpleQueue(string $mikrotikId): array
    {
        $query = new Query('/queue/simple/remove');
        $identifier = $this->getIdKey();
        $query->equal($identifier, $mikrotikId);
        return $this->client->query($query)->read();
    }

    public function enableSimpleQueue(string $mikrotikId): array
    {
        $query = new Query('/queue/simple/enable');
        $identifier = $this->getIdKey();
        $query->equal($identifier, $mikrotikId);
        return $this->client->query($query)->read();
    }

    public function disableSimpleQueue(string $mikrotikId): array
    {
        $query = new Query('/queue/simple/disable');
        $identifier = $this->getIdKey();
        $query->equal($identifier, $mikrotikId);
        return $this->client->query($query)->read();
    }

    // =========================================
    // IP ADDRESS & DHCP
    // =========================================

    public function getIpAddresses(): array
    {
        return $this->query('/ip/address/print');
    }

    public function getInterfaces(): array
    {
        return $this->query('/interface/print', [
            '.proplist' => 'name,type,running,disabled,comment'
        ]);
    }

    public function getDhcpServers(): array
    {
        return $this->query('/ip/dhcp-server/print');
    }

    public function getDhcpLeases(): array
    {
        return $this->query('/ip/dhcp-server/lease/print');
    }

    // =========================================
    // TRAFFIC / INTERFACE STATS
    // =========================================

    public function getInterfaceTraffic(string $interface): array
    {
        $query = new Query('/interface/monitor-traffic');
        $query->equal('interface', $interface);
        $query->equal('duration', '1');
        $query->equal('once', '');
        return $this->client->query($query)->read();
    }

    public function getInterfaceStats(): array
    {
        // Use .proplist to prevent timeout on routers with many dynamic interfaces
        return $this->query('/interface/print', [
            '.proplist' => 'name,type,rx-byte,tx-byte,running,disabled'
        ]);
    }

    // =========================================
    // PPPoE PROFILES
    // =========================================

    public function getPppProfiles(): array
    {
        return $this->query('/ppp/profile/print');
    }

    public function getHotspotProfiles(): array
    {
        return $this->query('/ip/hotspot/user/profile/print');
    }

    // =========================================
    // FIREWALL
    // =========================================

    public function getFirewallRules(): array
    {
        return $this->query('/ip/firewall/filter/print');
    }

    public function getNatRules(): array
    {
        return $this->query('/ip/firewall/nat/print');
    }

    public function __destruct()
    {
        $this->client = null;
    }
}
