# Quiz generation worker

First, make sure that you have an **llm-model** container (not needed for quiz generation using Aristote Dispatcher):

```
docker ps -a
```

If not, run this command

```
docker run --runtime nvidia --gpus all —name llm-model -v ~/.cache/huggingface:/root/.cache/huggingface -p 8000:8000 --ipc=host vllm/vllm-openai:v0.2.4 --model teknium/OpenHermes-2.5-Mistral-7B --dtype float16 --tensor-parallel-size 1
```

After a new Quiz Generation build with tag, these are the steps to follow on the Worker machine for prod environment for example (**Note** : quiz-gen folder contains .env.prod file necessary for running the docker container, you can make necessary changes if needed):

```
cd quiz-gen
docker stop quiz-generator-prod
docker rm quiz-generator-prod
docker pull jq422pa7.gra7.container-registry.ovh.net/aristote/opensource-quiz-generator:{tag}
docker run --env-file .env --add-host host.docker.internal:host-gateway -p 3000:3000 -p 3000:3000 --name quiz-generator-prod jq422pa7.gra7.container-registry.ovh.net/aristote/opensource-quiz-generator:{tag}
```

or simply (inside quiz-gen folder):

```
./update-prod.sh {tag}
```

To start process of getting a job from AristoteAPI and generating the quiz (assuming quiz-generator container and the llm-model container are running):

```
docker exec quiz-generator-prod python server/generate_quizz.py
```

This script can be used to handle switching on the llm-model container, waiting till it's responsive, then start the previous command, while taking into account a maximum number of parallel jobs :

```
./quiz-generation-cron-job.sh
```

# Transcription worker

#TODO: Gitllab CI Depoyment with tags

To update transcription worker, make a new build on your local machine and transfer it to the server:

```
docker save -o whisper.tar whisperapi-whisper
scp whisper.tar ubuntu@ip_adress:/home/ubuntu/transcription
```

These are the steps to follow on the Worker machine (**Note** : transcription folder contains .env file necessary for running the docker container and the .env.preprod/.env.prod for the appropriate Aristote URL and credentials to be used, you can make necessary changes if needed):

```
cd transcription
docker stop whisper
docker rm whisper
docker image rm whisperapi-whisper
docker load -i whisper.tar
docker run --runtime nvidia --gpus all --env-file .env -p 3001:3000 -v {MODEL_FOLDER_PATH}:/server_app/custom_model -v ./.env.prod:/server_app/.env.prod -v ./.env.preprod:/server_app/.env.preprod --name whisper whisperapi-whisper
```

or simply (inside transcription folder):

```
./update.sh
```

To start the process of getting a job from AristoteAPI and transcribing the media (assuming whisper container and the whisper container are running):

```
docker exec whisper python transcribe_aristote.py --env .env.prod
```

or

```
docker exec whisper python transcribe_aristote.py --env .env.preprod
```

This script can be used to handle switching on the whisper container, waiting till it's responsive, then start the previous command, while taking into account a maximum number of parallel jobs :

```
./transcription-cron-job.sh
```

# Evaluation worker

It uses quiz-generator container so make sure it's running.

To start the process of getting a job from AristoteAPI and evaluating a quiz

```
docker exec quiz-generator python server/evaluate_quizz.py
```

# Cron jobs

To access cronjob list :

```
crontab -e
```

There is **schedule.sh** that is run as a cron job and it handles the scheduling of quiz generation/transcription workers using **transcription-cron-job.sh** and 
**quiz-generation-cron-job.sh** because there is not enough VRAM from them to run simultaneously.


**evaluation-cron-job.sh** that is run as a separate cron job since it doesn't interfere with the other workers.

**quiz-generation-dispatcher-cron-job.sh** handles the quiz generation using Aristote Dispatcher, so no local LLM is needed.

There is also **evaluation-cron-job.sh** that is run as a separate cron job since it doesn't interfere with the other workers.
