# KOOOMO API

## Installation

1. Git clone the project
2. Run "composer install" to install all its dependencies
3. Copy .env.example to a new file called .env and set DB credentials(Create a new DB)
4. Run "php artisan key:generate" in the console
5. Run "php artisan migrate" in the console
6. Run "php artisan passport:install" in the console
7. You can run "php artisan db:seed" if you choose to test fake data or you can manually insert rows into tables as there are only 3 tables involved.
8. Run the project "php artisan serve" in the root directory to run locally your project or use docker/laradock to spin up the project containers.
9. Postman: Import the file called "kooomo.postman_collection.json"

## Endpoints
###### Explained in more detail in the postman file (kooomo.postman_collection.json) in root directory. You can import this file into Postman APP for quick API testing.

### Comments Explained
1. To get all comments for a given post: /api/comments/get/?post_id=19
2. To get all comments: /api/comments/get
3. To get one comment: /api/comment/get/?id=6
4. To delete comment: /api/comment/delete/?id=1
5. Include a valid token to only return Comments belonging to the token owner.

### Posts Explained
1. To get all Posts: /api/posts/get
2. To get one Post: /api/post/get/?id=6
3. To delete comment: /api/post/delete/?id=1
4. Include a valid token to only return Posts belonging to the token owner.

### GET /api/comments
```
http://127.0.0.1:8000/api/comments/get
```

### GET /api/comment
```
http://127.0.0.1:8000/api/comment/get
```

### POST
```
http://127.0.0.1:8000/api/comment/post
```

### PUT
```
http://127.0.0.1:8000/api/comment/put
```

### DELETE
```
http://127.0.0.1:8000/api/comment/delete
```

### GET /api/posts
```
http://127.0.0.1:8000/api/posts/get
```

### GET /api/post
```
http://127.0.0.1:8000/api/post/get
```

### POST
```
http://127.0.0.1:8000/api/post/post
```

### PUT
```
http://127.0.0.1:8000/api/post/put
```

### DELETE
```
http://127.0.0.1:8000/api/post/delete
```

### Author

Donal Lynch donal.lynch.msc@gmail.com