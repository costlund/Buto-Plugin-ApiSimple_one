# Buto-Plugin-ApiSimple_one
This is a simple API to retrieve information.

## Settings
```
plugin_modules:
  api_v1:
    plugin: api/simple_one
    settings:
      slack:
        webhook: _url_
        group: _group_
        domain_filter: www.world.com
      security:
        keys:
          -
            value: abcdef
            data:
              some_id: 1234
        _remote_addr:
          -
            value: '127.0.0.1'
      methods:
        users:
          plugin: some/plugin
          name: users
```
### slack
Optional to alert on Slack. Use param domain_filter to only send if on a specific domain.

### security/keys
Optional to verify user by a key. One could use data parameter to assosiate key with values.

### security/remote_addr
Optional to verify user by ip.

### methods
Here we send users to plugin some/plugin and method users along with some data.

## Url
```
/api_v1/users?key=abcdef
```

## Result
```
user:
  role:
    client: false
error: {  }
data:
  - James
  - Jane
```

### user
If param user/role/client is true user has sign in. 

### error
Param error is for error handling.

### data
This has value of the method involved in request.

## Output
Set output=yml to render yml instead of json.
```
/api_v1/users?key=abcdef&output=yml
```

## Method
Method example.
```
public function users($json, $settings, $key_data){
  $data = new PluginWfArray();
  $data->set('error', array());
  $data->set('data', array('James', 'Jane'));
  return $data->get();
}
```

## Access-Control-Allow-Origin
Set param xurl to client url to allow requests between different domains. 
In example below we call domain world.com from localhost. 
```
http://world.com?xurl=http://localhost
```
