## Large file submission plugin for Moodle Assignment

Once marking is complete, and after they have been stored in s3 for a minimum of 90 days, files will then be moved from s3 to glacier for cost-effective long term storage. 
File retrieval option is available and managed from the plugin interface.



## Cross-origin resource sharing (CORS) Settings for S3 bucket:
```
[
    {
        "AllowedHeaders": [
            "*"
        ],
        "AllowedMethods": [
            "GET",
            "HEAD",
            "PUT"
        ],
        "AllowedOrigins": [
            "*"
        ],
        "ExposeHeaders": ["ETag"],
        "MaxAgeSeconds": 3000
    }
]
```
