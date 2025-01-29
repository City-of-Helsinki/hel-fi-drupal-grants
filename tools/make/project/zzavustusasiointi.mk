DRUPAL_POST_INSTALL_TARGETS += drush-forms drush-locale
AVU_DRUPAL_FRESH_TARGETS := up build sync post-install

PHONY += drush-mm
drush-mm: ## drush locale:check; drush locale:update; drush cr
	$(call step, Disable maintenenance mode...\n)
	$(call drush,state:set system.maintenance_mode 0)

PHONY += drush-locale
drush-locale: ## drush locale:check; drush locale:update; drush cr
	$(call step, Import translations...\n)
	$(call drush,locale:check)
	$(call drush,cr)

PHONY += drush-forms
drush-forms: ## Export configuration
	$(call step,Import forms & overrides...\n)
	$(call drush,gwi --force -y)
	$(call drush,gwco -y)

PHONY += drush-gwi
drush-gwi: ## Export configuration
	$(call step,Import forms...\n)
	$(call drush,gwi -y)

PHONY += drush-gwco
drush-gwco: ## Export configuration
	$(call step,Import form overrides...\n)
	$(call drush,gwco -y)

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
	$(call drush,locale:check -y)
	$(call drush,gwi -y)
	$(call drush,locale:update -y)
	$(call drush,cr -y)

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
	$(call drush,cr -y)
else
	$(call step,Sync database from @$(DRUPAL_SYNC_SOURCE)...)
	$(call drush,sql-sync -y --structure-tables-key=common,key_value_expire @$(DRUPAL_SYNC_SOURCE) @self)
	$(call drush,cr -y)
endif

PHONY += drush-create-dump
drush-create-dump: FLAGS := --structure-tables-key=common,key_value_expire --extra-dump=--no-tablespaces
drush-create-dump: ## Create database dump to dump.sql
	$(call drush,sql-dump $(FLAGS) --result-file=${DOCKER_PROJECT_ROOT}/$(DUMP_SQL_FILENAME))

PHONY += drush-download-dump
drush-download-dump: ## Download database dump to dump.sql
	$(call drush,@$(DRUPAL_SYNC_SOURCE) sql-dump --structure-tables-key=common,key_value_expire > ${DOCKER_PROJECT_ROOT}/$(DUMP_SQL_FILENAME))

PHONY += fresh-config
fresh-config: ## Build fresh development environment and sync
	@$(MAKE) $(AVU_DRUPAL_FRESH_TARGETS)
	$(call drush, config:set config_ignore.settings ignored_config_entities '' -y)
	$(call drush,deploy -y)
