<?php

namespace App\Business\Dao\UM;

use App\Business\Model\Requester;
use Illuminate\Support\Facades\DB;

class APIDao
{
    public function __construct(Requester $requester)
    {
        $this->connection = 'pgsql_um';
        $this->requester = $requester;
    }

    public function canUserAccess($service, $routePrefix, $routeAction)
    {
        $query = DB::connection($this->connection)
            ->table('apis')
            ->join('menu_action_apis', 'menu_action_apis.api_id', 'apis.id')
            ->join('role_menus', function ($join) {
                $join->on('role_menus.menu_action_code', '=', 'menu_action_apis.menu_action_code');
                $join->on('role_menus.menu_code', '=', 'menu_action_apis.menu_code');
            })
            ->join('user_roles', 'user_roles.role_id', 'role_menus.role_id')
            ->where([
                ['apis.service', $service],
                ['apis.route_action', $routeAction],
                ['user_roles.tenant_id', $this->requester->getTenantId()],
                ['user_roles.user_id', $this->requester->getUserId()],
                ['user_roles.is_active', true]
            ]);

        if ($routePrefix) {
            $query->where('apis.route_prefix', $routePrefix);
        } else {
            $query->whereNull('apis.route_prefix');
        }
        
        return $query->exists();
    }

    public function isWhitelist($service, $routePrefix, $routeAction)
    {
        $query = DB::connection($this->connection)
            ->table('apis')
            ->where([
                ['apis.service', $service],
                ['apis.route_action', $routeAction],
                ['apis.is_whitelist', true],
            ]);

        if ($routePrefix) {
            $query->where('apis.route_prefix', $routePrefix);
        } else {
            $query->whereNull('apis.route_prefix');
        }
        
        return $query->exists();
    }
}
