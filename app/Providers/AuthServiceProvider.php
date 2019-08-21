<?php

namespace App\Providers;

use App\Business\Model\Requester;
use App\Business\Helper\JwtHandler;
use Emarref\Jwt\Claim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Business\Dao\UM\UserDao;
use App\Business\Dao\UM\APIDao;
use App\Business\Dao\UM\LogDao;
use App\Business\Dao\Core\AssignmentDao;

/**
 * Service provider to get information of requester.
 * @package App\Providers
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Requester::class, function ($app) {
            return new Requester();
        });
    }

    public function boot(Request $request)
    {
        $this->app['auth']->viaRequest('api', function ($request) {
            if( $request->path() === 'live')
                return true;

            if (env('APP_ENV') == 'testing') {
                $requester = app(Requester::class);
                $requester->setTenantId((int)env('TENANT_ID'));
                $requester->setCompanyId((int)env('COMPANY_ID'));
                $requester->setUserId((int)env('USER_ID'));

                return true;
            }

            //check authorization header first. If not exists, then send 401 response
            $authToken = null;

            if (!$this->validateAuthorizationHeader($request)) {
                info('AUTHLOG: Auth Header is not valid'); // for LOGGING
                return null;
            } else {
                $authToken = $this->parseAuthorizationHeader($request);
            }

            $jwtHandler = new JwtHandler();
            try {
                $payloads = $jwtHandler->verifyToken($authToken);
            } catch (\Exception $e) {
                expireToken($authToken);
                info('AUTHLOG: Token is Expired'); // for LOGGING
                return null;
            }

            $tenantId = $payloads->findClaimByName('tenantId')->getValue();
            $userId = $payloads->findClaimByName('userId')->getValue();
            $appId = $payloads->findClaimByName('applicationId')->getValue();

            // check if API accessed with application Id source same 
            // as appId on auth credential he got
            if ($request->applicationId != $appId) {
                info('AUTHLOG: ApplicationId Payload is not valid or missing'); // for LOGGING
                return null;
            }

            $requester = app(Requester::class);
            $requester->setTenantId($tenantId);
            $requester->setUserId($userId);

            $now = Carbon::now()->timestamp;
            $expiry = $payloads->findClaimByName(Claim\Expiration::NAME)->getValue();
            if (($now >= ($expiry - config('app.token_renew_window'))) && !isTokenBlocked($authToken)) {
                $newToken = $jwtHandler->renewToken($authToken);
                $requester->setToken($newToken);
                $requester->setTokenRenewed(true);
                renewToken($authToken, $newToken);
            } else {
                $requester->setToken($authToken);
                $requester->setTokenRenewed(false);
            }

            // check if user exist
            $userDao = new UserDao($requester);
            $user = $userDao->getOne($requester->getUserId());
            if (!$user) {
                info('AUTHLOG: User is not found'); // for LOGGING
                info('AUTHLOG: User id: ' . $requester->getUserId());
                return null;
            } else if (!$user->personId && !$user->isSa) {
                info('AUTHLOG: User has no person attached'); // for LOGGING
                return null;
            }

            $assignmentDao = new AssignmentDao($requester);

            if ($request->input('companyId')) {
                $requester->setCompanyId($request->companyId);

                if (!$user->isSa) { // if user is super admin, let it pass
                    // Check if user has access to this company.
                    if (!$assignmentDao->isPersonActiveInCompany($request->companyId, $user->personId)) {
                        info('AUTHLOG: Person has no active assignment'); // for LOGGING
                        info('AUTHLOG: Person Id:' . $user->personId);
                        return null; // no active assignments = no access
                    }

                    // Check if user owns this employee ID.
                    if ($request->input('employeeId') && $request->applicationId != config('constant.admin_app_id')) {
                        if (!$assignmentDao->doesEmployeeIdBelongToPerson(
                            $request->companyId,
                            $user->personId,
                            $request->employeeId
                        )) {
                            info('AUTHLOG: This EmployeeId is not belonged to this person'); // for LOGGING
                            info('AUTHLOG: EmployeeId: ' . $request->employeeId); // for LOGGING
                            return null;
                        }
                    }
                }
            }

            // Check if user can access this endpoint.
            $route = explode('/', $this->app->request->getRequestUri());

            if (count($route) === 3) {
                $routePrefix = $route[1];
                $routeAction = $route[2];
            } else if (count($route) === 2) {
                $routePrefix = null;
                $routeAction = $route[1];
            } else {
                info('AUTHLOG: Route prefix is not recognized'); // for LOGGING
                return null; // unknown form of route, YOU SHALL NOT PASS!
            }

            // Insert a log for Audit Trail.
            $logId = LogDao::insertLog($requester, $userId, 'time', $routePrefix, $routeAction);
            if($logId) {
                $requester->setLogId($logId);
            }

            // Only use access check on admin app
            if ($request->applicationId != config('constant.admin_app_id')) {
                return true;
            } else {
                if (!$userDao->isSa()) {
                    $haveAccessAPI = $this->checkApiAuthorization($requester, $routePrefix, $routeAction);
                    if (!$haveAccessAPI) {
                        info('AUTHLOG: No Access to this API'); // for LOGGING
                        info('API : ' . $routePrefix . ' ' . $routeAction);
                        return null;
                    }
                }
            }

            return true;
        });
    }

    private function checkApiAuthorization($requester, $routePrefix, $routeAction)
    {
        $apiDao = new APIDao($requester);
        if (!$apiDao->isWhitelist(env('SERVICE_NAME'), $routePrefix, $routeAction)) {
            if (!$apiDao->canUserAccess(env('SERVICE_NAME'), $routePrefix, $routeAction)) {
                return null;
            }
        }

        return true;
    }

    private function validateAuthorizationHeader(Request $request)
    {
        if (!Str::startsWith(strtolower($request->headers->get('authorization')), $this->getAuthorizationMethod())) {
            return false;
        }

        return true;
    }

    private function getAuthorizationMethod()
    {
        return 'bearer';
    }

    private function parseAuthorizationHeader(Request $request)
    {
        return trim(str_ireplace($this->getAuthorizationMethod(), '', $request->header('authorization')));
    }
}
