PHONY += test-clean-data
test-clean-data: ## Run Robot framework tests in docker container (all tests)
	$(call test/clean-env-for-testing.sh)

PHONY += test-robot
test-robot: ## Run Robot framework tests in docker container (all tests)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-public
test-robot-public: ## Run Robot framework tests in docker container (Public)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite public ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-profile
test-robot-profile: ## Run Robot framework tests in docker container (Profile)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite profile ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-oma-asiointi
test-robot-oma-asiointi: ## Run Robot framework tests in docker container (Oma asiointi)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite oma_asiointi ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-form-kasvatus
test-robot-form-kasvatus: ## Run Robot framework tests in docker container (Kasvatus ja koulutus yleisavustushakemus)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite form_kasvatus_ja_koulutus_yleisavustu ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-form-yleis
test-robot-form-yleis: ## Run Robot framework tests in docker container (Yleisavustushakemus)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite form_yleisavustushakemus ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-form-kult-kehit
test-robot-form-kult-kehit: ## Run Robot framework tests in docker container (Kulttuurin kehittämisavustus)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite form_kulttuurin_kehittamis ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-form-kult-toimi
test-robot-form-kult-toimi: ## Run Robot framework tests in docker container (Kulttuurin toiminta-avustus)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite form_kulttuurin_toiminta ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-form-kult-proj
test-robot-form-kult-proj: ## Run Robot framework tests in docker container (Kulttuurin projektiavustus)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite form_kulttuurin_projekti ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-form-kasko-ip-lisa
test-robot-form-kasko-ip-lisa: ## Run Robot framework tests in docker container (Iltapäivätoiminnan lisäavustus)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite form_kasko_ip_lisa ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0

PHONY += test-robot-form-nuorlomaleir
test-robot-form-nuorlomaleir: ## Run Robot framework tests in docker container (Nuorisotoiminnan loma-aikojen leiriavustus)
	docker run \
			-v $(PWD)/test/logs:/opt/robotframework/reports:Z \
			-v $(PWD)/test:/opt/robotframework/tests:Z \
      -e ROBOT_OPTIONS="--variable environment:local --variable browser:chrome --suite form_nuorlomaleir ${ROBOT_OPTIONS}" \
			--add-host $(DRUPAL_HOSTNAME):127.0.0.1 \
			--net="host" \
			-it \
			ppodgorsek/robot-framework:5.0.0
