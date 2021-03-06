<?php
namespace SpotifyWebAPI;

class Session
{
    private $accessToken = '';
    private $clientId = '';
    private $clientSecret = '';
    private $expires = 0;
    private $redirectUri = '';
    private $refreshToken = '';

    /**
     * Constructor
     * Set up client credentials.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     *
     * @return void
     */
    public function __construct($clientId, $clientSecret, $redirectUri)
    {
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setRedirectUri($redirectUri);
    }

    /**
     * Get the authorization URL.
     *
     * @param array|object $options Optional. Options for the authorization URL.
     * - array scope Optional. Scope(s) to request from the user.
     * - boolean show_dialog Optional. Whether or not to force the user to always approve the app. Default is false.
     * - string state Optional. A CSRF token.
     *
     * @return string
     */
    public function getAuthorizeUrl($options = array())
    {
        $defaults = array(
            'scope' => array(),
            'show_dialog' => false,
            'state' => ''
        );

        $options = array_merge($defaults, (array) $options);
        $parameters = array(
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $options['scope']),
            'show_dialog' => $options['show_dialog'] ? 'true' : 'false',
            'state' => $options['state']
        );

        return Request::ACCOUNT_URL . '/authorize/?' . http_build_query($parameters);
    }

    /**
     * Get the access token.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Get the client ID.
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Get the client secret.
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * Get the client's redirect URI.
     *
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * Refresh a access token.
     *
     * @return bool
     */
    public function refreshToken()
    {
        $payload = base64_encode($this->getClientId() . ':' . $this->getClientSecret());

        $parameters = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken
        );

        $headers = array(
            'Authorization' => 'Basic ' . $payload
        );

        $response = Request::account('POST', '/api/token', $parameters, $headers);
        $response = $response['body'];

        if (isset($response->access_token)) {
            $this->accessToken = $response->access_token;
            $this->expires = $response->expires_in;
            //$this->refreshToken = $response->refresh_token; // @todo Doesn't seem like Spotify is supplying a new refresh token

            return true;
        }

        return false;
    }

    /**
     * Request a access token.
     *
     * @param string $code The authorization code from Spotify.
     *
     * @return bool
     */
    public function requestToken($code)
    {
        $parameters = array(
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getRedirectUri()
        );

        $response = Request::account('POST', '/api/token', $parameters);
        $response = $response['body'];

        if (isset($response->access_token)) {
            $this->accessToken = $response->access_token;
            $this->expires = $response->expires_in;
            $this->refreshToken = $response->refresh_token;

            return true;
        }

        return false;
    }

    /**
     * Set the client ID.
     *
     * @return void
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * Set the client secret.
     *
     * @return void
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * Set the client's redirect URI.
     *
     * @return void
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }
}
