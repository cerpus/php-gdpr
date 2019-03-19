<?php

namespace Cerpus\Gdpr\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Cerpus\LaravelAuth\Service\CerpusAuthService;

class GdprMiddleware
{
    protected $requiredScopes = [
        'read'
    ];

    public function handle($request, Closure $next)
    {
        try {
            // Check for bearer token authentication
            if (!$bearerToken = $request->bearerToken()) {
                return response()->json([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'message' => "Missing Bearer token."
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($this->bearerTokenDoesNotHaveClientCredentials($bearerToken)) {
                return response()->json([
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => "Token has the wrong grant type."
                ], Response::HTTP_FORBIDDEN);
            }

            // Check for required scopes
            if ($this->bearerTokenDoesNotHaveRequiredScopes($bearerToken)) {
                return response()->json([
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => "Token is missing the required scope(s)."
                ], Response::HTTP_FORBIDDEN);
            }
        } catch (\Throwable $t) {
            Log::error(__METHOD__ . ' (' . $t->getCode() . '): ' . $t->getMessage());

            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => "Internal server error."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //Everything checks out, continue
        return $next($request);
    }

    protected function bearerTokenDoesNotHaveClientCredentials($bearerToken): bool
    {
        return !$this->checkIfBearerTokenGrantTypeIs($bearerToken, 'client_credentials');
    }

    protected function checkIfBearerTokenGrantTypeIs($bearerToken, $grantType = 'client_credentials'): bool
    {
        $grantTypeMatches = false;

        if ($checkTokenResponse = $this->getTokenResponse($bearerToken)) {
            $grantTypeMatches = (bool)(mb_strtolower($checkTokenResponse->getGrantType()) === mb_strtolower($grantType));
        }

        return $grantTypeMatches;
    }

    protected function bearerTokenDoesNotHaveRequiredScopes($bearerToken): bool
    {
        $hasRequiredScopes = false;

        if ($checkTokenResponse = $this->getTokenResponse($bearerToken)) {
            $checkedScopes = array_intersect($this->requiredScopes, $checkTokenResponse->getScope());

            $hasRequiredScopes = (bool)(count($checkedScopes) !== count($this->requiredScopes));
        }

        return $hasRequiredScopes;
    }

    protected function getTokenResponse($bearerToken, $useCache = true)
    {
        try {
            $cacheKey = "GdprApiTokenResponse|$bearerToken";

            $tokenResponse = Cache::get($cacheKey, null);
            if (!$tokenResponse && $useCache) {
                /** @var CerpusAuthService $authService */
                $authService = App::make(CerpusAuthService::class);

                if ($tokenResponse = $authService->getCheckTokenRequest($bearerToken)->execute()) {
                    $cacheForSeconds = Carbon::now()->addSeconds(300);
                    if ($tokenResponse->getExpiry()) {
                        $cacheForSeconds = Carbon::now()->addSeconds($tokenResponse->getExpiry() - 10);
                    }

                    if ($useCache) {
                        Cache::put($cacheKey, $tokenResponse, $cacheForSeconds);
                    }
                }
            }
        } catch (\Throwable $t) {
            Log::error(__METHOD__ . ': (' . $t->getCode() . ')' . $t->getMessage());

            throw $t;
        }

        return $tokenResponse;
    }
}
