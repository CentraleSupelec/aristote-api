# Forcing models and/or infrastructres for transcription/enrichment/translation by client

When creating/editing a `client` in **Sonata Admin Panel**, you can specify which `model` and/or `infrastructure` you want for `transcription`, `enrichment` or `translation` or  of the enrichments created by that `client`.

To associate a `model`/`infrastructure` to a **transcription**, **enrichment** or **translation** worker, you can do it the creation/edit page of the worker in **Sonata Admin**.

**Example:**

Given the following enrichment workers :

| Worker Name                            | Model      | Infrastructure | Can take compatible enrichments with missing model and/or infrastructure|
|----------------------------------------|------------|----------------|-------------------------------------------------------------------------|
| `enrichment_worker_llama3_cs_1`        | Llama3     | CS             | True
| `enrichment_worker_llama3_ups`         | Llama3     | UPS            | True
| `enrichment_worker_openhermes_cs`      | OpenHermes | CS             | True
| `enrichment_worker_openhermes_ups`     | OpenHermes | UPS            | True
| `enrichment_worker_llama3_cs_2`        | Llama3     | CS             | True

---

These are the available models/infrastructures for each client :

| Client     | Forced Enrichment Model  | Forced Enrichment Infrastructure  | Available Models/Infrastructures for selection when creating an enrichment|
|------------|--------------------------|----------------------------------|----------------------------------------------------------------------------|
| client_1   | –                        | –                                | `Llama3@CS`, `Llama3@UPS`, `OpenHermes@CS`, `OpenHermes@UPS`               |
| client_2   | Llama3                   | –                                | `Llama3@CS`, `Llama3@UPS`                                                  |
| client_3   | –                        | CS                               | `Llama3@CS`, `OpenHermes@CS`                                               |
| client_4   | Llama3                   | UPS                              | `Llama3@UPS`                                                               |

Let's take the case of **client_3** :


| Chosen model when creating enrichment| Chosen infrastructure | Enrichment created ?                  |Workers that can take the job                                    |
|--------------------------------------|-----------------------|---------------------------------------|-----------------------------------------------------------------|
| -                                    | -                     |                                       | `enrichment_worker_llama3_cs_1`, `enrichment_worker_llama3_cs_2`, `enrichment_worker_openhermes_cs`|
| `DeepSeek`                           | -                     | **Error :** model not available           | -                                                               |
| `Llama3`                             | `UPS`                 | **Error :** infrastructure not available  | -                                                               |
| `Llama3`                             | -                     | OK                                    | `enrichment_worker_llama3_cs_1`, `enrichment_worker_llama3_cs_2`|
| `Llama3`                             | `CS`                  | OK                                    | `enrichment_worker_llama3_cs_1`, `enrichment_worker_llama3_cs_2`|
| `OpenHermes`                         | `UPS`                 | **Error :** infrastructure not available  | -                                                               |
| `OpenHermes`                         | -                     | OK                                    | `enrichment_worker_openhermes_cs`                               |
| `OpenHermes`                         | `CS`                  | OK                                    | `enrichment_worker_openhermes_cs`                               |

**NOTE:**

If for a certain worker has "Can take compatible enrichments with missing model and/or infrastructure" set to False, it can only take enrichments with exact match to its defined model/infrastructure.
