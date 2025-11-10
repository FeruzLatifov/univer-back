<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OAuth\OAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * OAuth2 Controller
 *
 * Implements OAuth2 Authorization Code Flow
 * Compatible with standard OAuth2 spec
 *
 * @OA\Tag(
 *     name="OAuth2",
 *     description="OAuth2 Authorization endpoints"
 * )
 */
class OAuthController extends Controller
{
    public function __construct(
        private OAuthService $oauthService
    ) {}

    /**
     * Authorization endpoint
     *
     * @OA\Get(
     *     path="/api/v1/oauth/authorize",
     *     tags={"OAuth2"},
     *     summary="OAuth2 Authorization Request",
     *     description="Step 1 of OAuth2 flow - User authorization",
     *     @OA\Parameter(
     *         name="client_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="redirect_uri",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="uri")
     *     ),
     *     @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", enum={"code"})
     *     ),
     *     @OA\Parameter(
     *         name="scope",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Authorization page"),
     *     @OA\Response(response=400, description="Invalid request")
     * )
     */
    public function authorize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:oauth_client,id',
            'redirect_uri' => 'required|url',
            'response_type' => 'required|in:code',
            'scope' => 'nullable|string',
            'state' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Get client
        $client = $this->oauthService->getClient($request->client_id);

        if (!$client || $client->isRevoked()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or revoked client',
            ], 400);
        }

        // Validate redirect URI
        if ($client->redirect !== $request->redirect_uri) {
            return response()->json([
                'success' => false,
                'message' => 'Redirect URI mismatch',
            ], 400);
        }

        // Return authorization page data
        // In real implementation, this would show consent screen
        return response()->json([
            'success' => true,
            'data' => [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                ],
                'redirect_uri' => $request->redirect_uri,
                'scope' => $request->scope,
                'state' => $request->state,
                'message' => 'User should be presented with authorization consent screen',
            ],
        ]);
    }

    /**
     * Authorize (grant access)
     *
     * @OA\Post(
     *     path="/api/v1/oauth/authorize",
     *     tags={"OAuth2"},
     *     summary="Grant Authorization",
     *     description="User grants access to client",
     *     security={{"bearer":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "redirect_uri"},
     *             @OA\Property(property="client_id", type="integer"),
     *             @OA\Property(property="redirect_uri", type="string", format="uri"),
     *             @OA\Property(property="scope", type="string"),
     *             @OA\Property(property="state", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Authorization granted, redirect with code"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function grant(Request $request): JsonResponse
    {
        // User must be authenticated
        $user = auth('employee-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:oauth_client,id',
            'redirect_uri' => 'required|url',
            'scope' => 'nullable|string',
            'state' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Validate client
        if (!$this->oauthService->validateClient($request->client_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid client',
            ], 400);
        }

        // Parse scopes
        $scopes = $request->scope ? explode(' ', $request->scope) : null;

        // Generate authorization code
        $authCode = $this->oauthService->generateAuthorizationCode(
            $request->client_id,
            $user->id,
            $scopes
        );

        // Build redirect URL
        $redirectUrl = $request->redirect_uri . '?' . http_build_query([
            'code' => $authCode->id,
            'state' => $request->state,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'redirect_url' => $redirectUrl,
                'code' => $authCode->id,
                'expires_in' => $authCode->expiresIn(),
            ],
        ]);
    }

    /**
     * Token endpoint
     *
     * @OA\Post(
     *     path="/api/v1/oauth/token",
     *     tags={"OAuth2"},
     *     summary="Get Access Token",
     *     description="Exchange authorization code or refresh token for access token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grant_type", "client_id"},
     *             @OA\Property(property="grant_type", type="string", enum={"authorization_code", "refresh_token"}),
     *             @OA\Property(property="client_id", type="integer"),
     *             @OA\Property(property="client_secret", type="string"),
     *             @OA\Property(property="code", type="string", description="Required for authorization_code grant"),
     *             @OA\Property(property="refresh_token", type="string", description="Required for refresh_token grant"),
     *             @OA\Property(property="redirect_uri", type="string", format="uri")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Access token issued",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid request")
     * )
     */
    public function token(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grant_type' => 'required|in:authorization_code,refresh_token',
            'client_id' => 'required|integer',
            'client_secret' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            if ($request->grant_type === 'authorization_code') {
                return $this->handleAuthorizationCodeGrant($request);
            } elseif ($request->grant_type === 'refresh_token') {
                return $this->handleRefreshTokenGrant($request);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'error' => 'unsupported_grant_type',
        ], 400);
    }

    /**
     * Handle authorization code grant
     */
    private function handleAuthorizationCodeGrant(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'redirect_uri' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Missing required parameters',
                'errors' => $validator->errors(),
            ], 400);
        }

        $tokens = $this->oauthService->exchangeAuthorizationCode(
            $request->code,
            $request->client_id,
            $request->client_secret
        );

        return response()->json($tokens);
    }

    /**
     * Handle refresh token grant
     */
    private function handleRefreshTokenGrant(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Missing refresh_token',
                'errors' => $validator->errors(),
            ], 400);
        }

        $tokens = $this->oauthService->refreshAccessToken(
            $request->refresh_token,
            $request->client_id
        );

        return response()->json($tokens);
    }

    /**
     * Revoke token
     *
     * @OA\Post(
     *     path="/api/v1/oauth/revoke",
     *     tags={"OAuth2"},
     *     summary="Revoke Token",
     *     description="Revoke an access or refresh token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type_hint", type="string", enum={"access_token", "refresh_token"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Token revoked")
     * )
     */
    public function revoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'token_type_hint' => 'nullable|in:access_token,refresh_token',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'errors' => $validator->errors(),
            ], 400);
        }

        $revoked = $this->oauthService->revokeAccessToken($request->token);

        return response()->json([
            'success' => $revoked,
            'message' => $revoked ? 'Token revoked' : 'Token not found',
        ]);
    }

    /**
     * Get user info (for resource server)
     *
     * @OA\Get(
     *     path="/api/v1/oauth/userinfo",
     *     tags={"OAuth2"},
     *     summary="Get User Info",
     *     description="Get authenticated user information using OAuth access token",
     *     @OA\Parameter(
     *         name="access_token",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="User information"),
     *     @OA\Response(response=401, description="Invalid token")
     * )
     */
    public function userinfo(Request $request): JsonResponse
    {
        $accessToken = $request->input('access_token') ?? $request->bearerToken();

        if (!$accessToken) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Missing access token',
            ], 400);
        }

        $token = $this->oauthService->validateAccessToken($accessToken);

        if (!$token) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Token is invalid or expired',
            ], 401);
        }

        return response()->json([
            'sub' => $token->_user,
            'client_id' => $token->_client,
            'scopes' => $token->scopes->pluck('id')->toArray(),
            'exp' => $token->expires_at->timestamp,
        ]);
    }
}
