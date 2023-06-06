# Robot tests

## Deleting test data from ATV

Script `test/clean-env-for-testing.sh` removes documents with given  

`/app/test/.test_env` file is used to store variables for testing.


`APP_ENV='LOCALJ'

ATV_BASE_URL="url for atv environment"

ATV_API_KEY="Same key env uses."

ATV_VERSION="atv version, current is v1"

ATV_SERVICE="Service name for ATV api key"

declare -a USER_IDT=('user ids as an bash array')

declare -a Y_TUNNUKSET=('test community business ids as an array')
`

Within the container we can then run `test/clean-env-for-testing.sh`.


## Running with make / docker

Run command `make test-robot`

This creates and runs a docker container with python and robot framework installed. The whole test suite is ran and logs will be in `test/logs` folder.

To run only specific tests, run `ROBOT_OPTIONS="-t Test_name_here" make test-robot
`, where `Test_name_here` is the name of the test case in robot file, spaces replaced with underscores.

## Running in local environment

### Requirements

- python3
- pip
- chromedriver

### Install

- `cd test`
- `python3 -m venv env`
- `source env/bin/activate`
- `pip install -r requirements.txt`
- `rfbrowser init`

### Running

- `cd test`
- `source env/bin/activate`
- `ROBOT_OPTIONS="--variable environment:local --variable browser:chrome" robot -d logs/ tests/*.robot`
