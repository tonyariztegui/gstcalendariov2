# Quiz – Guide administrateur

## Activation

1. **Base de données** : les tables `quiz_domande` et `quiz_tentativi` sont créées automatiquement à la première visite de la gestion Quiz (ou exécuter `public/assets/sql/quiz_tables.sql`).
2. **Step Configurazione** : cocher **Quiz** sous *Sotto modalità* → **Salva configurazione**.
3. **Step Quiz** :
   - Règles (nombre de questions, type de parcours, seuil de réussite) → **Salva configurazione**
   - **Pagina scelta** (si mode *Scelta tra testo e audio*) : containers + libellés → **Salva configurazione**
   - **Intestazione pagina domande** : containers d’en-tête → **Salva configurazione**
   - **Domande** : formulaire **Salva** (hors sauvegarde concorso global)
   - **Containers pagina quiz** (template domanda) : **Salva contenitori quiz**

## Types de parcours (`quiz.mode`)

| Mode | Comportement public |
|------|---------------------|
| `standard` | Domandes texte uniquement |
| `audio` | Domandes audio uniquement |
| `choice` | Page scelta testo/audio → puis domandes du type choisi |

## Fichiers principaux

| Fichier | Rôle |
|---------|------|
| `manager/views/concorso/step-quiz.php` | Règles + layout (dans `#config-form`) |
| `manager/views/concorso/step-quiz-domande.php` | CRUD domandes (hors `#config-form`) |
| `manager/views/quiz_domande.php` | Gestion domandes + template bloc domanda |
| `app/logic/quiz/flow.php` | Flux public multi-étapes |
| `app/config/content.json` | `quiz_enabled`, `quiz.*`, `pages.quiz_page`, `pages.quiz_choice_page` |

## Personnalisation

- **Pagina scelta** : `pages.quiz_choice_page.containers[]` + `choice_labels` (standard, audio, submit)
- **Pagina domande** : `pages.quiz_page.containers[]` (en-tête) + `question_block_style` (template `{{DOMANDA_N}}`, `{{TESTO_DOMANDA}}`, `{{RISPOSTA}}`, `{{OPZIONI}}`)
- **Preview wizard** : colonnes DOM sans footer (comme Thank-you / Attesa) ; iframe domande avec `?wizard=1` sans footer

## Flux public

```
Inscription → /quiz
  → [fidelity si activé]
  → [scelta si mode=choice]
  → domandes
  → /grazie
```

Les données d’inscription restent en session jusqu’à la fin du quiz (`gst_quiz_pending_registration`).
