DRUPAL_POST_INSTALL_TARGETS += drush-gwi
AVU_DRUPAL_FRESH_TARGETS := up build sync post-install

PHONY += drush-gwi
drush-gwi: ## Export configuration
	$(call step,Import forms...\n)
	$(call drush,gwi -y)

PHONY += drush-rebuild-db
drush-rebuild-db: ## Export configuration
	$(call step,Drop DB...\n)
	$(call drush,sql-drop -y)
	$(call step,Import DB...\n)
	$(call $(drush sql:connect) < dump.sql)
	$(call step,Import forms...\n)
	$(call drush,gwi -y)

PHONY += drush-rebuild
drush-rebuild: ## Export configuration
	$(call step,Composer install...\n)
	$(call composer,install)
	$(call step,Run deploy...\n)
	$(call drush,deploy -y)
	$(call step,Import forms...\n)
	$(call drush,gwi -y)

PHONY += rebuild-theme
rebuild-theme: ## Installs dependencies for HDBT subtheme
	$(call node,/public/themes/custom/hdbt_subtheme,"npm install")
	$(call node,/public/themes/custom/hdbt_subtheme,"npm run build")

PHONY += drush-sync-db
drush-sync-db: ## Sync database
	$(call drush,sql-drop --quiet -y)
ifeq ($(DUMP_SQL_EXISTS),yes)
	$(call step,Import local SQL dump...)
	$(call drush,sql-query --file=${DOCKER_PROJECT_ROOT}/$(DUMP_SQL_FILENAME) && echo 'SQL dump imported')
else
	$(call step,Sync database from @$(DRUPAL_SYNC_SOURCE)...)
	$(call drush,sql-sync -y --structure-tables-key=common,key_value_expire @$(DRUPAL_SYNC_SOURCE) @self)
endif

PHONY += drush-create-dump
drush-create-dump: FLAGS := --structure-tables-key=common,key_value_expire --extra-dump=--no-tablespaces
drush-create-dump: ## Create database dump to dump.sql
	$(call drush,sql-dump $(FLAGS) --result-file=${DOCKER_PROJECT_ROOT}/$(DUMP_SQL_FILENAME))

PHONY += drush-download-dump
drush-download-dump: ## Download database dump to dump.sql
	$(call drush,@$(DRUPAL_SYNC_SOURCE) sql-dump --structure-tables-key=common,key_value_expire > ${DOCKER_PROJECT_ROOT}/$(DUMP_SQL_FILENAME))
