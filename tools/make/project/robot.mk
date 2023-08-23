PHONY += test-clean-data
test-clean-data: ## Run Robot framework tests in docker container (all tests)
	$(call test/clean-env-for-testing.sh)
	
PHONY += test-robot
test-robot: ## Run Robot Framework tests in a Docker container
	docker run --rm \
		-v $(PWD)/test:/test \
		-v $(PWD)/test/logs:/test/logs \
		-e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome ${ROBOT_OPTIONS}" \
		--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
		--net="host" \
		-it \
		marketsquare/robotframework-browser:latest \
		bash -c "robot --outputdir /test/logs /test"
