# Project actors, components and terminology

**Aristote** is an AI infrastructure which helps enrich pedagogical contents such as videos, providing automatic metadata and MCQ generation. It is made available through the use of the **AristoteAPI**.

An **AristoteAPI** `client` is a piece of software implemented in an edTech solution such as an on-demand video platform. The `client` implements the **AristoteAPI** in order to get enriched data from a source `Media`.

Posting a `Media` to the **AristoteAPI** creates an `Enrichment` object which will be processed by **Aristote's workers**.

The source `Media` is usually a video file (or most probably an URL to a video file), but other media will be supported in the future. The `Enrichment` of the `Media` made by the **Aristote's workers** consists of:
- generated metadata such as a title, a description text, tags, and classification into a list of media-types and disciplines whose possible values are given by the `client`.
- a transcription for Video and/or audio files
- an MCQ

The result of an `Enrichment` is called a `Version`, and the `Version`produced by the AI is called an `InitialVersion`.

`Enrichments` by AI are very usefull and time savers but are not perfect. It is important that the edTech product, in which the **AristoteAPI** `client` is integrated, implements a user interface to let the end user correct the generated output. In order to improve **Aristote AI**, it is also important the the corrected `Version` is posted by the `client`using the **AristoteAPI**


# Client API Charts

## Databasase diagram

![Image not found](/docs/bdd.png)

## Posting a `Media` and creating an `Enrichment`
The first step is to post a media to be processed by **Aristote**.

The *client* can post the URL of a video to create an `Enrichment`.
**Aristote** returns immediately an `Id` for this `Enrichment` and a status `WAITING_MEDIA_UPLOAD`.

**Aristote's workers** will then process the `Enrichment`:
- the **media worker** will download the media, and the `Enrichment` status will change to `UPLOADING_MEDIA` then `WAITING_MEDIA_TRANSCRIPTION`
- the **transcription worker** will process the video and the enrichment status will change to `TRANSCRBING_MEDIA` then to `WAITING_AI_ENRICHMENT`
- the **AI worker** will then process the `Enrichment` and its status will switch to `AI_ENRICHING`. If the `Evaluation` option has not been required, then the state switches to `SUCCESS` or `FAILURE`
- If the optionnal `Evaluation` feature has been asked at the `Enrichment` creation time, the state will switch to `WAITING_EVALUATION`then `EVALUATING`before going to `SUCCESS`or `ERROR`
- Failure can occur at several steps

Here is a basic state diagram of the statuses for the `Enrichment`:
```mermaid
stateDiagram-v2
   WAITING_EVALUATION: WAITING_EVALUATION
   [*] --> UPLOADING_MEDIA
   UPLOADING_MEDIA --> WAITING_MEDIA_TRANSCRIPTION: Media type = video/audio
   UPLOADING_MEDIA --> WAITING_AI_ENRICHMENT: Media type = SRT/VTT
   WAITING_MEDIA_TRANSCRIPTION --> TRANSCRBING_MEDIA    
   TRANSCRBING_MEDIA --> WAITING_AI_ENRICHMENT: generateMetadata OR generateQuiz OR generateNotes
   WAITING_AI_ENRICHMENT --> AI_ENRICHING
   AI_ENRICHING --> WAITING_TRANSLATION: translateTo is set
   AI_ENRICHING --> WAITING_EVALUATION: translateTo is not set AND aiEvaluation is set
   WAITING_EVALUATION --> EVALUATING
   WAITING_TRANSLATION --> TRANSLATING
   TRANSCRBING_MEDIA --> SUCCESS: !generateMetadata AND !generateQuiz AND !generateNotes
   AI_ENRICHING --> SUCCESS: translateTo is not set AND aiEvaluation is not set
   TRANSLATING --> SUCCESS
   EVALUATING --> SUCCESS
   AI_ENRICHING --> FAILURE
   TRANSCRBING_MEDIA --> FAILURE
   UPLOADING_MEDIA --> FAILURE
   EVALUATING --> FAILURE
   TRANSLATING --> FAILURE
   SUCCESS --> AI_ENRICHING: Request new enrichment
   FAILURE --> AI_ENRICHING: Request new enrichment
   FAILURE --> [*]: Delete enrichment
   SUCCESS --> [*]: Delete enrichment
```

Creating an `Enrichment` can be done by posting a URL from which **Aristote** will download the `Media`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: POST /api/v1/enrichments/url
   AristoteAPI-->>Client: Status & enrichmentId
```

Alternatively, it is possible tu upload a `Media` file to create the `Enrichment`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: POST /api/v1/enrichments/upload
   AristoteAPI-->>Client: Status & enrichmentId
```

When the `Enrichment` has been processed, either successfully or with a failure, an optional call to a *client* `Webhook` can be made to notify the creation or failure of the `Enrichment`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: POST /api/v1/enrichments/upload
   AristoteAPI-->>Client: Status & enrichmentId
   AristoteAPI-->>-Client: optional feedback to WebHookURL (status,initialVersionId)
```

## Getting the status of `Enrichments`
At anytime a *client* can request the status of an `Enrichment` and **AristoteAPI** will answers if the *identity of the client* corresponds to the **owner** of the `EnrichmentId` provided:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: GET /api/v1/enrichments/{id}
   AristoteAPI-->>Client: Status (& initialVersionId when available)
```

At anytime a *client* can request the list of `Enrichments` and **AristoteAPI** will answers with the list of `Enrichments` **owned by** this particular `client`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: GET /api/v1/enrichments
   AristoteAPI-->>Client: List Enrichments Status & initialVersionId when available
```

## Deleting an `Enrichment`
The `client` **owning** an `Enrichment` can delete it:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: DELETE /api/v1/enrichments/{id}
   AristoteAPI-->>Client: Status
```

## Getting the content of an `Enrichment`'s `Version`
The result of an `Enrichment` is called a `Version`.
A successfully processed `Enrichment` containes an initial `Version` identified by and an `initialVersionId`.

The *client* can get the content of a `Version` by calling the `AristoteAPI`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: GET /api/v1/enrichments/{enrichmentId}/versions/{versionId}
   AristoteAPI-->>Client: Status, transcript, metadata (title, description, tags, media-type), discipline, MCQ
```

## Managing multiple `Enrichment` `Versions`

The initial `Version` is the one produced by the AI, but the end user may want to correct the output. For instance fixing bad tags, fixing classification into media types or disciplines, or changing questions and/or answers of the MCQ.

The `client` must push the proposed corrections made by the end user un a new `Version` by using the  **AristoteAPI**:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: POST /api/v1/enrichments/{id}/versions
   AristoteAPI-->>Client: Status, new versionId
```

The `client` may get the list of `Versions` of a particular `Enrichment`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: GET /api/v1/enrichments/{id}/versions
   AristoteAPI-->>Client: Status, list of Versions
```

The `client` may get the latest `Version` of a particular `Enrichment`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: GET /api/v1/enrichments/{id}/versions/latest
   AristoteAPI-->>Client: Status, latest Version
```

The `client` **owning** an `Enrichment` may delete a `Version` if it's not the `InitialVersion`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: DELETE /api/v1/enrichments/{enrichmentId}/versions/{versionId}
   AristoteAPI-->>Client: Status, latest Version
```

## Feedbacks and Evaluations

The `client` **owning** an `Enrichment` should send an `Evaluation` of the `InitialVersion` generated by the AI. this can be done globally for the whole `Enrichment`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: POST /api/v1/enrichments/{enrichmentId}/versions/{versionId}/evaluate
   AristoteAPI-->>Client: Status, updated last Evaluation date
```

This can be done more granularly on each `Question`:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: POST /api/v1/enrichments/{enrichmentId}/versions/{versionId}/mcq/{mcqId}
   AristoteAPI-->>Client: Status
```

Or even at the `Answer option` level:
```mermaid
sequenceDiagram
   participant Client
   participant AristoteAPI
   Client->>+AristoteAPI: POST /api/v1/enrichments/{enrichmentId}/versions/{versionId}/mcq/{mcqId}/choice/{choiceId}
   AristoteAPI-->>Client: Status
```

## Forcing models and/or infrastructres for transcription/enrichment/translation by client

When creating/editing a `client` in **Sonata Admin Panel**, you can specify which `model` and/or `infrastructure` you want for `transcription`, `enrichment` or `translation` or  of the enrichments created by that `client`.

To associate a `model`/`infrastructure` to a **transcription**, **enrichment** or **translation** worker, you can do it the creation/edit page of the worker in **Sonata Admin**.
