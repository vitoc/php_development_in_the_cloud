<?php
/**
 * Manages actions that can be performed with users.
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt  
 */
class UserManager extends Manager
{
    /**
     * Zend OpenId Consumer object
     *
     * @var Zend_OpenId_Consumer
     */
    protected $_consumer = NULL;
    /**
     * Oauth object
     *
     * @var Oauth
     */
    protected $_oauth = NULL;
    /**
     * Instantiates an OpenId consumer object if necessary.
     */    
    protected function _constructConsumer()
    {
        if (is_null($this->_consumer)) {
            $this->_consumer = new Zend_OpenId_Consumer();
        }
    }
    /**
     * Instantiates an Oauth object if necessary.
     */    
    protected function _constructOauth()
    {
        if (is_null($this->_oauth)) {
            $this->_oauth = new OAuth(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1);
        }
    }
    /**
     * Set the current user's Id in session.
     * 
     * @param string $userId
     *
     */
    public function setCurrentUser($userId)
    {
        session_start();
        $_SESSION['userId'] = $userId;                
    }
    /**
     * Check if there is a session established, otherwise redirect to an access
     * request page.
     */
    public function checkCurrentUser()
    {
        if (!isset($_SESSION)) {
            session_start();
            if (!isset($_SESSION['userId'])) {
                header('Location: access.php');
            }
        }
    }
    /**
     * Returns true if the OpenId passed in is within the allowed user list.
     * 
     * @param string $openIdUrl
     *
     * @return bool
     */
    public function allowedUser($openIdUrl)
    {
        $userOpenIds = file(PM_USERS, FILE_IGNORE_NEW_LINES);
        if (($userOpenIds !== FALSE) && in_array($openIdUrl, $userOpenIds)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    /**
     * Attempts to log the user into PictureMe using the specified OpenId.
     * 
     * @param string $openIdUrl
     *
     * @return mixed
     */
    public function userLogin($openIdUrl)
    {
        $this->_constructConsumer();
        if (!($this->allowedUser($openIdUrl) && $this->_consumer->login($openIdUrl, 'access.php'))) {
            return FALSE;
        }
    }
    /**
     * Manages requests submitted to the access request view.
     * 
     * @param array $request
     *
     * @return mixed
     */
    public function manageRequest($request)
    {
        if (!empty($request['openIdUrl'])) {
            if ($this->userLogin($request['openIdUrl'])) {
                header('Location: index.php');
            } else {
                return "Unable to login";
            }
        } else if (!empty($request['openid_claimed_id'])) {
            if ($this->verifyOpenId($request)) {
                header('Location: index.php');
            } else {
                return "OpenID verification failed";
            }
        } else if (!empty($request['logout'])) {
            $this->logout();
            return "Logged Out";
        }
        return NULL;
    }
    /**
     * Verifies that the information from a OpenID provider corrently identifies
     * a user.
     * 
     * @param array $params
     *
     * @return bool
     */
    public function verifyOpenId($params)
    {
        $this->_constructConsumer();
        if ($this->_consumer->verify($params, $id)) {
            $this->setCurrentUser($id);
            return TRUE;
        } else {
            return FALSE;          
        }
    }
    /**
     * Check if there is an access authorisation information associated to the user 
     * is stored within the application. If not, obtain a request token from Google
     * to acces Picasa and redirect to Google's authorisation page. 
     *
     * @return UserManager
     */
    public function checkAuthorization()
    {
        if (is_null($this->_localStore->get($_SESSION['userId']))) {
            $this->_constructOauth();
            $scope = urlencode('http://picasaweb.google.com/data/');
            $callback = urlencode("http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}");
            $requestToken = $this->_oauth->getRequestToken("https://www.google.com/accounts/OAuthGetRequestToken?scope={$scope}");
            $this->_localStore->put($_SESSION['userId'], serialize($requestToken));
            header("Location: https://www.google.com/accounts/OAuthAuthorizeToken?oauth_token={$requestToken['oauth_token']}&oauth_callback={$callback}");
        } else {
            $userTokens = unserialize($this->_localStore->get($_SESSION['userId']));
            if (!isset($userTokens['access_token'])) {
                throw new Exception('User authorization failed.');
            }
        }
        return $this;
    }
    /**
     * Checks if $token authorized by the user with Google is similar to that 
     * associated to the user within the application. If so, obtain access token.
     *
     * @return UserManager
     */
    public function userAuthorized($token)
    {
        $userTokens = unserialize($this->_localStore->get($_SESSION['userId']));
        if ($token === $userTokens['oauth_token'] && !$this->getStoredAccessToken()) {
            $this->_constructOauth();
	        $this->_oauth->setToken($userTokens['oauth_token'], $userTokens['oauth_token_secret']); 
            $accessToken = $this->_oauth->getAccessToken('https://www.google.com/accounts/OAuthGetAccessToken');
            $userTokens['access_token'] = $accessToken['oauth_token'];
            $userTokens['access_token_secret'] = $accessToken['oauth_token_secret'];
            $this->_localStore->put($_SESSION['userId'], serialize($userTokens));
        }
        return $this;
    }
    /**
     * Returns an Oauth object with access token.
     *
     * @return UserManager
     */
    public function getAccess()
    {
        $this->_constructOauth();
        $userTokens = unserialize($this->_localStore->get($_SESSION['userId']));
        $this->_oauth->setToken($userTokens['access_token'], $userTokens['access_token_secret']);
        //$this->_oauth->setAuthType(OAUTH_AUTH_TYPE_AUTHORIZATION);
        return $this->_oauth;

    }
    /**
     * Returns the access token stored within the application if available.
     *
     * @return UserManager
     */
    public function getStoredAccessToken()
    {
        $userTokens = unserialize($this->_localStore->get($_SESSION['userId']));
        return isset($userTokens['access_token']) ? $userTokens['access_token'] : FALSE;
    }
    /**
     * Standard clean-up of all session setup and subsequently destroy session.
     */
    public function logout()
    {
        session_start();
        $_SESSION = array();
        if (ini_get('session.use_cookies')) 
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
