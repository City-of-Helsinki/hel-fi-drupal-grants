# Deleting test data from ATV

Script `robot/clean-env-for-testing.sh` removes documents with given  

`/app/test/.test_env` file is used to store variables for testing.


`APP_ENV='LOCALJ'

ATV_BASE_URL="url for atv environment"

ATV_API_KEY="Same key env uses."

ATV_VERSION="atv version, current is v1"

ATV_SERVICE="Service name for ATV api key"

declare -a USER_IDT=('user ids as an bash array')

declare -a Y_TUNNUKSET=('test community business ids as an array')
`

Within the container we can then run `robot/clean-env-for-testing.sh`.

