<?php
require_once __DIR__ . '/../config/google_config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

class GoogleAuth
{
    private $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setClientId(GOOGLE_CLIENT_ID);
        $this->client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $this->client->setRedirectUri(GOOGLE_REDIRECT_URL);
        $this->client->setScopes(GOOGLE_SCOPES);
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function handleCallback($code)
    {
        try {
            // Get token from code
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            $this->client->setAccessToken($token);

            // Get user info
            $google_oauth = new Google_Service_Oauth2($this->client);
            $google_account_info = $google_oauth->userinfo->get();

            return [
                'email' => $google_account_info->email,
                'name' => $google_account_info->name,
                'picture' => $google_account_info->picture,
                'id' => $google_account_info->id
            ];
        } catch (Exception $e) {
            error_log("Google Auth Error: " . $e->getMessage());
            return false;
        }
    }
}
