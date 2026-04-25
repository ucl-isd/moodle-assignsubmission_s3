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
## Next up
- The CORS policy could be refined
- We have not yet tested the retrieval of submissions from Glacier aspect yet as it does take 90 days before files can be sent there, we will, as soon as our test files do reach this. If adopting early, you will want to make sure to follow this project to pull in any updates if we end up finding issues with this.
