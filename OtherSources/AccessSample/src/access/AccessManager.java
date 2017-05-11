package access;
import java.util.Map;
import java.util.HashMap;
import com.google.appengine.api.users.UserServiceFactory;
import com.google.appengine.api.users.User;
import com.google.appengine.api.users.UserService;
/**
 * Sample class that illustrates the use of App Engine's  Google Accounts 
 * service to identify users. Can be expanded to handle user access to an 
 * application hosted on App Engine.
 * 
 * @author  Vito Chin <vito@php.net>
 */
public class AccessManager
{
    /**
     * Google Account User service
     *
     * @var UserService
     */
    UserService userService;
    /**
     * Google Account User
     *
     * @var User
     */
    User user;

    public AccessManager()
    {
        this.userService = UserServiceFactory.getUserService();
        this.user        = userService.getCurrentUser();
    }
    /**
     * Gets an instance of the WorkerManager.
     *
     * @param String hostname
     *     
     * @return Map<String, String> access URL and link text
     */
    public Map<String, String> getAccessInfo(String hostname)
    {
        Map<String, String> accessMap = new HashMap<String, String>();
        if (this.user == null) {
            accessMap.put("url", this.userService.createLoginURL(hostname));
            accessMap.put("display", "Login");
        } else {
            accessMap.put("url", this.userService.createLogoutURL(hostname));
            accessMap.put("display", "Logout");
        }
        return accessMap;
    }
}
