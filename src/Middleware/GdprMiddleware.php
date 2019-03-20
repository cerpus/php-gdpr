<?php

namespace Cerpus\Gdpr\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Cerpus\AuthCore\TokenResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Cerpus\LaravelAuth\Service\CerpusAuthService;

class GdprMiddleware
{
    protected $requiredScopes = [
        'read',
    ];

    protected $bearerToken = null;
    protected $tokenResponse = null;
    protected $identityResponse = null;

    protected $tokenResponseCacheTime = 1800;
    protected $identityResponseCacheTime = 1800;

    public function handle($request, Closure $next)
    {
        try {
            if (!$bearerToken = $request->bearerToken()) {
                return response()->json([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'message' => "Missing Bearer token."
                ], Response::HTTP_UNAUTHORIZED);
            }

            $this->setBearerToken($bearerToken);

            if ($this->bearerTokenIsNotActive()) {
                return response()->json([
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => "Inactive token."
                ], Response::HTTP_FORBIDDEN);
            }

            if ($this->bearerTokenGrantTypeIs('client_credentials')) { // Machine to machine token with the appropriate scopes
                if ($this->bearerTokenDoesNotHaveRequiredScopes()) {
                    return response()->json([
                        'code' => Response::HTTP_FORBIDDEN,
                        'message' => "Token is missing the required scope(s): " . implode(',', $this->requiredScopes),
                    ], Response::HTTP_FORBIDDEN);
                }
            } elseif ($this->bearerTokenGrantTypeIs('authorization_code')) { // An user with admin rights
                if ($this->bearerTokenDoesNotHaveAdminAccess()) {
                    return response()->json([
                        'code' => Response::HTTP_FORBIDDEN,
                        'message' => "Token does not have admin access."
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => "Token has the wrong grant type."
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

    protected function bearerTokenDoesNotHaveAdminAccess()
    {
        $hasAdminAccess = false;

        if ($this->bearerTokenGrantTypeIs('authorization_code')) {
            $identity = $this->getIdentity();
            $hasAdminAccess = (bool)$identity->admin ?? false;
        }

        return !$hasAdminAccess;
    }

    protected function bearerTokenGrantTypeIs($grantType = []): bool
    {
        $grantTypeMatches = false;

        if (is_string($grantType)) {
            $grantType = explode(',', $grantType);
        }

        if ($checkTokenResponse = $this->getTokenResponse()) {
            $checkGrant = mb_strtolower($checkTokenResponse->getGrantType());
            $grantTypeMatches = in_array($checkGrant, $grantType);
        }

        return $grantTypeMatches;
    }

    protected function bearerTokenDoesNotHaveRequiredScopes(): bool
    {
        $hasRequiredScopes = false;

        if ($checkTokenResponse = $this->getTokenResponse()) {
            $checkedScopes = array_intersect($this->requiredScopes, $checkTokenResponse->getScope());
            $hasRequiredScopes = (bool)(count($checkedScopes) === count($this->requiredScopes));
        }

        return !$hasRequiredScopes;
    }

    protected function bearerTokenIsNotActive()
    {
        $tokenIsActive = false;

        if ($checkTokenResponse = $this->getTokenResponse()) {
            $tokenIsActive = $checkTokenResponse->isActive();
        }

        return !$tokenIsActive;
    }

    protected function getTokenResponse($useCache = true)
    {
        if ($this->tokenResponse) {
            return $this->tokenResponse;
        }

        try {
            $cacheKey = "GdprApiTokenResponse|{$this->getBearerToken()}";

            $tokenResponse = Cache::get($cacheKey, null);
            if (!$tokenResponse && $useCache) {
                /** @var CerpusAuthService $authService */
                $authService = App::make(CerpusAuthService::class);

                if ($tokenResponse = $authService->getCheckTokenRequest($this->getBearerToken())->execute()) {
                    $cacheForSeconds = Carbon::now()->addSeconds($this->tokenResponseCacheTime);
                    if ($tokenResponse->getExpiry()) {
                        $cacheForSeconds = Carbon::now()->addSeconds($tokenResponse->getExpiry() - 10);
                    }

                    if ($useCache) {
                        Cache::put($cacheKey, $tokenResponse, $cacheForSeconds);
                    }
                }
            }

            if ($tokenResponse) {
                $this->tokenResponse = $tokenResponse;
            }
        } catch (\Throwable $t) {
            Log::error(__METHOD__ . ': (' . $t->getCode() . ')' . $t->getMessage());

            throw $t;
        }

        return $this->tokenResponse;
    }

    protected function getIdentity($useCache = true)
    {
        if ($this->identityResponse) {
            return $this->identityResponse;
        }

        try {
            $cacheKey = "GdprIdentity|{$this->bearerToken}";

            $identity = Cache::get($cacheKey, null);
            if (!$identity && $useCache) {
                /** @var CerpusAuthService $authService */
                $authService = App::make(CerpusAuthService::class);
                $tokenResponse = new TokenResponse();
                $tokenResponse->access_token = $this->getBearerToken();

                if ($identity = $authService->getIdentityRequest($tokenResponse)->execute()) {
                    if ($useCache) {
                        $cacheForSeconds = Carbon::now()->addSeconds($this->identityResponseCacheTime);
                        Cache::put($cacheKey, $identity, $cacheForSeconds);
                    }
                }
            }
            if ($identity) {
                $this->identityResponse = $identity;
            }
        } catch (\Throwable $t) {
            Log::error(__METHOD__ . ': (' . $t->getCode() . ')' . $t->getMessage());

            throw $t;
        }

        return $this->identityResponse;
    }


    public function getBearerToken()
    {
        return $this->bearerToken;
    }

    public function setBearerToken($bearerToken)
    {
        $this->bearerToken = $bearerToken;
    }
}
