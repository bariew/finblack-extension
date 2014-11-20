Finblack extension.
===================
For searching finance blacklists data API.

Usage:
======

1. Getting all users by full name search.

```
   $client = new \bariew\finblack\Client([
        'baseUrl' => 'http://blacklist.dev',
        'username' => 'pt',
        'apiKey' => 123123
    ]);
    print_r($client->request('index', ['names' => 'asdf']));
```

2. Searching user for matching.
```
    $client = new \bariew\finblack\Client([
        'baseUrl' => 'http://blacklist.dev',
        'username' => 'pt',
        'apiKey' => 123123
    ]);
    print_r($client->request('compare', ['search' => ['names' => 'tuan', 'list_type' => 1], 'compare' => ['passport' => '1212 343434']]));
```

