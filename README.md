## Large file submission plugin for Moodle Assignment

Once marking is complete, and after they have been stored in s3 for a minimum of 90 days, files will then be moved from s3 to glacier for cost-effective long term storage. 
File retrieval option is available and managed from the plugin interface.

## Next up
- The CORS policy could be refined
- We have not yet tested the retrieval of submissions from Glacier aspect yet as it does take 90 days before files can be sent there, we will, as soon as our test files do reach this. If adopting early, you will want to make sure to follow this project to pull in any updates if we end up finding issues with this.

## Contributing
We built this plugin as part of our long term vision to make Moodle Assignment meet all of our assessment needs, another strand of that is to expand the marking capabilities of Moodle Assignment.   
[Find out more about Joining the Multiple Marking project](https://github.com/ucl-isd/moodle-mod_coursework/wiki/Joining-the-Multiple-Marking-project)

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
