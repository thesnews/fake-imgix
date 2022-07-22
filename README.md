# fake-imgix

Local development stand-in for Imgix. Don't use this as a base for some half-assed attempt at a production replacement for a image management service. You're going to have a bad time.

Intended to be used in conjunction with services like [FakeS3](https://github.com/jubos/fake-s3) and [FakeSQS](https://github.com/iain/fake_sqs).

## Use with docker-compose

```
version: "3.4"

services:
    # Use fakes3 as the backing store
    fakes3:
        image: fingershock/fakes3
        volumes:
            - ./fakes3:/fakes3_data
        ports:
            - "3031:8000"
    fakeimgix:
        image: snworks/fakeimgix:latest
        ports:
            - "3032:8082"
        depends_on:
            - fakes3
        environment:
            - IMGIX_SOURCE_ROOT=http://fakes3:8000
```
