.PHONY: docker


default:

clean:
	rm -rf ./build
	mkdir build

docker:
	docker build --rm -f Dockerfile -t fakeimgix:latest .
	docker tag fakeimgix:latest snworks/fakeimgix:latest
	docker push snworks/fakeimgix:latest

