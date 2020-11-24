# KOOOMO API

## Installation

1. Git clone the project
2. Run "composer install" to install all its dependencies
3. Copy .env-example to a new file called .env and set DB credentials(Create a new DB)
4. Run "php artisan key:generate" in the console
5. Run "php artisan migrate" in the console
6. Run "php artisan passport:install" in the console
7. You can run "php artisan db:seed" if you choose to test fake data or you can manually insert rows into tables as there are only 3 tables involved.
8. Run the project "php artisan serve" in the root directory to run locally your project or use docker/laradock to spin up the project containers.
9. Import the file called "endpoint-testing.json"

## Endpoints
###### Explained in more detail in the postman file (kooomo.postman_collection.json) in root directory. You can import this file into Postman APP for quick API testing.

### GET /api/comment
```
> http://127.0.0.1:8000/api/comment/get
```

### POST
```
> http://127.0.0.1:8000/api/comment/post
```

### PUT
```
> http://127.0.0.1:8000/api/comment/put
```

### GET /api/post
```
> http://127.0.0.1:8000/api/post/get
```

### POST
```
> http://127.0.0.1:8000/api/post/post
```

### PUT
```
> http://127.0.0.1:8000/api/post/put
```

### Author

Donal Lynch donal.lynch.msc@gmail.com