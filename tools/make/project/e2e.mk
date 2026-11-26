# Ignore command line arguments passed as targets.
%:
	@:

# Use the Playwright version from the e2e package lock for the container image.
export PLAYWRIGHT_VERSION := $(shell awk '/"node_modules\/@playwright\/test"/ {getline; gsub(/[^0-9.]/, ""); print; exit}' e2e/package-lock.json)

delete-docs-all: ## Delete all documents from the environment
	bash ./e2e/clean-env.sh

delete-docs-draft: ## Delete draft documents from the environment
	bash ./e2e/clean-env.sh --draft

# List form test projects from the Playwright config.
FORM_PROJECTS = awk -F"'" '/name:/ {name = $$2} /testMatch: .\/forms\/[0-9]+_[a-z_]+\/\*./ {print name}' e2e/playwright.config.ts
DEFAULT_FORM_PROJECT := forms-50-taide-ja-kulttuuriavustukset-tai

# Print projects in two columns and highlight the default.
FORM_PROJECTS_TABLE = awk -v def="$$default" -v c='$(CYAN)' -v g='$(GREEN)' -v n='$(NO_COLOR)' \
	'{ a[NR] = $$0 } END { rows = int((NR + 1) / 2); for (i = 1; i <= rows; i++) { line = ""; \
	for (k = i; k <= NR; k += rows) { name = a[k]; sub(/^forms-/, "", name); \
	line = line sprintf("%s%4d)%s %s%-37s%s", (k == def ? g : c), k, n, (k == def ? g : ""), name, n) } print line } }'

PHONY += e2e
e2e: ## Run one form E2E test or all React forms. Example: make e2e forms-50-taide-ja-kulttuuriavustukset-tai
	@projects=$$($(FORM_PROJECTS)); \
	arg='$(filter-out $@,$(MAKECMDGOALS))'; \
	if [ -n "$$arg" ]; then projects=$$(echo "$$projects" | grep -F -- "$$arg"); fi; \
	count=$$(echo "$$projects" | grep -c .); \
	if [ "$$count" -eq 0 ]; then printf "${RED}No form test matches: $$arg${NO_COLOR}\n"; exit 1; \
	elif [ "$$count" -eq 1 ]; then project=$$projects; \
	else \
		default=$$(echo "$$projects" | grep -nx '$(DEFAULT_FORM_PROJECT)' | cut -d: -f1); \
		printf "\n⭐ ${YELLOW}Select a form test${NO_COLOR}\n\n"; \
		echo "$$projects" | $(FORM_PROJECTS_TABLE); \
		printf "\n${CYAN}   a)${NO_COLOR} All React forms                      ${CYAN}   q)${NO_COLOR} Quit\n"; \
		printf "\nRun test for$${default:+ ${GREEN}[$$default]${NO_COLOR}}: "; read n; \
		case "$$n" in \
			a) project=forms-react ;; \
			q) exit 0 ;; \
			*) project=$$(echo "$$projects" | sed -n "$${n:-$$default}p") ;; \
		esac; \
	fi; \
	[ -n "$$project" ] || { printf "${RED}Invalid selection${NO_COLOR}\n"; exit 1; }; \
	printf "\n⭐ ${YELLOW}Starting e2e container with Playwright $(PLAYWRIGHT_VERSION)${NO_COLOR}\n\n"; \
	docker compose --profile testing up -d e2e || exit 1; \
	printf "\n⭐ ${YELLOW}Running $$project${NO_COLOR}\n\n"; \
	if docker compose exec e2e sh -c "npm install -y --silent && npx playwright test --project $$project $(if $(HEADED),--headed) < /dev/null"; then \
		printf "\n✅ ${GREEN}Tests passed.${NO_COLOR} Open the report with ${CYAN}make e2e-report${NO_COLOR}\n\n"; \
	else \
		printf "\n❌ ${RED}Tests failed.${NO_COLOR} Open the report with ${CYAN}make e2e-report${NO_COLOR}\n\n"; \
		exit 1; \
	fi

PHONY += e2e-report
e2e-report: ## Open the last E2E HTML report
	@open e2e/playwright-report/index.html 2>/dev/null || xdg-open e2e/playwright-report/index.html
