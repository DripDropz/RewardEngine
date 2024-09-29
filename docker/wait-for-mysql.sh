#!/bin/bash

while [ $(docker ps | grep -c "healthy.*rewardengine-mysql$") == 0 ]
do
  echo "Waiting for database to be healthy"
  sleep 1s
done
