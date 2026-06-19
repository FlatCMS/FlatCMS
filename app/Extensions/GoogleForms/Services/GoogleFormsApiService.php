<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Extensions\GoogleForms\Services;

use App\Core\FlatFile;
use App\Core\I18n;

final class GoogleFormsApiService
{
    private GoogleFormsOAuthService $oauth;
    private FlatFile $forms;
    private FlatFile $responses;

    public function __construct()
    {
        I18n::load('GoogleForms');
        $this->oauth = new GoogleFormsOAuthService();
        $this->forms = FlatFile::for('extensions/google-forms/forms');
        $this->responses = FlatFile::for('extensions/google-forms/responses');
    }

    public function listForms(bool $refresh = false): array
    {
        if (!$refresh) {
            $cached = $this->forms->all();
            if (is_array($cached) && $cached !== []) {
                return array_values($cached);
            }
        }

        $query = "mimeType='application/vnd.google-apps.form' and trashed=false";
        $url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
            'q' => $query,
            'fields' => 'files(id,name,webViewLink,modifiedTime,createdTime)',
            'orderBy' => 'modifiedTime desc',
            'pageSize' => 100,
        ]);

        $payload = $this->oauth->request('GET', $url);
        $files = is_array($payload['files'] ?? null) ? $payload['files'] : [];

        $this->clearStore($this->forms);

        foreach ($files as $file) {
            if (is_array($file)) {
                $this->forms->create([
                    'id' => (string) ($file['id'] ?? ''),
                    'name' => (string) ($file['name'] ?? ''),
                    'webViewLink' => (string) ($file['webViewLink'] ?? ''),
                    'modifiedTime' => (string) ($file['modifiedTime'] ?? ''),
                    'createdTime' => (string) ($file['createdTime'] ?? ''),
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return array_values($this->forms->all() ?: []);
    }

    public function getForm(string $formId): array
    {
        return $this->oauth->request('GET', 'https://forms.googleapis.com/v1/forms/' . rawurlencode($formId));
    }

    public function syncResponses(string $formId): array
    {
        $this->clearStore($this->responses);

        $form = $this->getForm($formId);
        $schema = $this->extractFormSchema($form);

        $responses = [];
        $pageToken = null;

        do {
            $params = [
                'pageSize' => 100,
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $url = 'https://forms.googleapis.com/v1/forms/' . rawurlencode($formId) . '/responses?' . http_build_query($params);
            $payload = $this->oauth->request('GET', $url);

            $batch = is_array($payload['responses'] ?? null) ? $payload['responses'] : [];

            foreach ($batch as $response) {
                if (!is_array($response)) {
                    continue;
                }

                $normalized = $this->normalizeResponse($response, $schema);
                $responses[] = $normalized;
                $this->responses->create($normalized);
            }

            $pageToken = isset($payload['nextPageToken']) ? (string) $payload['nextPageToken'] : null;
        } while ($pageToken);

        return $responses;
    }

    public function responses(): array
    {
        $items = $this->responses->all();

        return is_array($items) ? array_values($items) : [];
    }

    public function dashboard(): array
    {
        $responses = $this->responses();
        $last = null;

        foreach ($responses as $response) {
            if (!is_array($response)) {
                continue;
            }

            $submitted = (string) ($response['lastSubmittedTime'] ?? '');
            if ($last === null || $submitted > (string) ($last['lastSubmittedTime'] ?? '')) {
                $last = $response;
            }
        }

        return [
            'total' => count($responses),
            'lastSubmittedTime' => $last['lastSubmittedTime'] ?? null,
            'lastResponseId' => $last['responseId'] ?? null,
        ];
    }

    private function normalizeResponse(array $response, array $schema): array
    {
        $answers = [];
        $answersLabeled = [];
        $rawAnswers = is_array($response['answers'] ?? null) ? $response['answers'] : [];
        $questions = is_array($schema['questions'] ?? null) ? $schema['questions'] : [];
        $order = is_array($schema['order'] ?? null) ? $schema['order'] : [];

        foreach ($rawAnswers as $questionId => $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $answers[(string) $questionId] = $this->answerToString($answer);
        }

        $seen = [];

        foreach ($order as $questionId) {
            $questionId = (string) $questionId;

            if (!array_key_exists($questionId, $answers)) {
                continue;
            }

            $seen[$questionId] = true;
            $question = is_array($questions[$questionId] ?? null) ? $questions[$questionId] : [];

            $answersLabeled[] = [
                'question_id' => $questionId,
                'label' => (string) ($question['label'] ?? $questionId),
                'type' => (string) ($question['type'] ?? ''),
                'value' => (string) $answers[$questionId],
            ];
        }

        foreach ($answers as $questionId => $value) {
            $questionId = (string) $questionId;

            if (isset($seen[$questionId])) {
                continue;
            }

            $question = is_array($questions[$questionId] ?? null) ? $questions[$questionId] : [];

            $answersLabeled[] = [
                'question_id' => $questionId,
                'label' => (string) ($question['label'] ?? $questionId),
                'type' => (string) ($question['type'] ?? ''),
                'value' => (string) $value,
            ];
        }

        $respondentEmail = (string) ($response['respondentEmail'] ?? '');

        return [
            'responseId' => (string) ($response['responseId'] ?? ''),
            'createTime' => (string) ($response['createTime'] ?? ''),
            'lastSubmittedTime' => (string) ($response['lastSubmittedTime'] ?? ''),
            'respondentEmail' => $respondentEmail,
            'summary' => $this->buildSummary($answersLabeled, $respondentEmail),
            'answers' => $answers,
            'answers_labeled' => $answersLabeled,
            'raw' => $response,
            'synced_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function answerToString(array $answer): string
    {
        if (isset($answer['textAnswers']['answers']) && is_array($answer['textAnswers']['answers'])) {
            $values = [];

            foreach ($answer['textAnswers']['answers'] as $item) {
                if (is_array($item) && isset($item['value'])) {
                    $values[] = trim((string) $item['value']);
                }
            }

            $values = array_values(array_filter($values, static fn (string $value): bool => $value !== ''));

            if (count($values) > 1) {
                return implode("\n", $values);
            }

            return $values[0] ?? '';
        }

        if (isset($answer['fileUploadAnswers']['answers']) && is_array($answer['fileUploadAnswers']['answers'])) {
            return __('google_forms_answer_file_upload', 'GoogleForms');
        }

        if (isset($answer['grade'])) {
            return __('google_forms_answer_graded', 'GoogleForms');
        }

        return '';
    }

    private function extractFormSchema(array $form): array
    {
        $questions = [];
        $order = [];

        $items = is_array($form['items'] ?? null) ? $form['items'] : [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $this->collectQuestionFromItem($item, $questions, $order);
            }
        }

        return [
            'formId' => (string) ($form['formId'] ?? ''),
            'title' => (string) ($form['info']['title'] ?? ''),
            'questions' => $questions,
            'order' => $order,
        ];
    }

    private function collectQuestionFromItem(array $item, array &$questions, array &$order): void
    {
        $title = trim((string) ($item['title'] ?? ''));
        $itemId = (string) ($item['itemId'] ?? '');

        if (isset($item['questionItem']['question']) && is_array($item['questionItem']['question'])) {
            $question = $item['questionItem']['question'];
            $questionId = (string) ($question['questionId'] ?? $itemId);

            if ($questionId !== '') {
                $this->addQuestion($questions, $order, $questionId, $title !== '' ? $title : $questionId, $this->questionType($question));
            }
        }

        if (isset($item['questionGroupItem']['questions']) && is_array($item['questionGroupItem']['questions'])) {
            foreach ($item['questionGroupItem']['questions'] as $groupQuestion) {
                if (!is_array($groupQuestion)) {
                    continue;
                }

                $questionId = (string) ($groupQuestion['questionId'] ?? '');
                $rowTitle = trim((string) ($groupQuestion['rowQuestion']['title'] ?? ''));
                $label = trim($title . ($rowTitle !== '' ? ' — ' . $rowTitle : ''));

                if ($questionId !== '') {
                    $this->addQuestion($questions, $order, $questionId, $label !== '' ? $label : $questionId, $this->questionType($groupQuestion));
                }
            }
        }
    }

    private function addQuestion(array &$questions, array &$order, string $questionId, string $label, string $type): void
    {
        if (!isset($questions[$questionId])) {
            $order[] = $questionId;
        }

        $questions[$questionId] = [
            'label' => $label,
            'type' => $type,
        ];
    }

    private function questionType(array $question): string
    {
        if (isset($question['choiceQuestion']['type'])) {
            return (string) $question['choiceQuestion']['type'];
        }

        foreach ([
            'textQuestion' => 'TEXT',
            'scaleQuestion' => 'SCALE',
            'dateQuestion' => 'DATE',
            'timeQuestion' => 'TIME',
            'fileUploadQuestion' => 'FILE_UPLOAD',
            'rowQuestion' => 'ROW',
        ] as $key => $type) {
            if (isset($question[$key])) {
                return $type;
            }
        }

        return 'QUESTION';
    }

    private function buildSummary(array $answersLabeled, string $respondentEmail): array
    {
        $email = $this->findEmailAnswer($answersLabeled);
        if ($email === '') {
            $email = $respondentEmail;
        }

        $company = $this->findAnswer($answersLabeled, [
            'raison sociale',
            'nom de la structure',
            'nom du cabinet',
            'nom de l’entreprise',
            'nom de l entreprise',
            'nom de la société',
            'nom de la societe',
            'société',
            'societe',
            'structure',
            'cabinet',
            'entreprise',
            'organisation',
        ], [
            'adresse',
            'email',
            'e-mail',
            'mail',
            'site',
            'url',
            'activité',
            'activite',
            'secteur',
            'domaine',
            'fonction',
            'rôle',
            'role',
        ]);

        $contact = $this->findAnswer($answersLabeled, [
            'nom et prénom',
            'nom et prenom',
            'nom prénom',
            'nom prenom',
            'nom du contact',
            'personne référente',
            'personne referente',
            'interlocuteur',
            'contact principal',
            'votre nom',
            'prénom et nom',
            'prenom et nom',
        ], [
            'mode de contact',
            'préférence de contact',
            'preference de contact',
            'contact souhaité',
            'contact souhaite',
            'suite du projet',
            'suite',
            'projet',
            'rendez-vous',
            'rdv',
            'nda',
            'modalité',
            'modalite',
            'email',
            'e-mail',
            'mail',
            'téléphone',
            'telephone',
        ]);

        if ($contact === '' || $this->looksLikeWorkflowAnswer($contact) || $this->looksLikeEmail($contact)) {
            $contact = $this->findPersonLikeAnswer($answersLabeled);
        }

        $activity = $this->findAnswer($answersLabeled, [
            'activité principale',
            'activite principale',
            'secteur d’activité',
            'secteur d activite',
            'secteur',
            'type de structure',
            'profil de la structure',
            'domaine d’activité',
            'domaine d activite',
            'métier',
            'metier',
            'profession',
        ], [
            'email',
            'e-mail',
            'mail',
            'adresse',
            'site',
            'url',
            'contact',
            'nom',
            'prénom',
            'prenom',
        ]);

        if ($activity === '' || $this->looksLikeEmail($activity)) {
            $activity = $this->findBusinessLikeAnswer($answersLabeled);
        }

        return [
            'company' => $company,
            'contact' => $contact,
            'email' => $email,
            'activity' => $activity,
            'need' => $this->findAnswer($answersLabeled, ['besoin principal', 'besoin', 'objectif', 'usage', 'cas d’usage', "cas d'usage", 'problématique', 'problematique', 'attente', 'priorité', 'priorite']),
            'volume' => $this->findAnswer($answersLabeled, ['volume documentaire', 'nombre de documents', 'documents', 'volume']),
            'budget' => $this->findAnswer($answersLabeled, ['budget', 'enveloppe', 'estimation', 'montant']),
            'status' => 'Nouveau',
        ];
    }

    private function findEmailAnswer(array $answersLabeled): string
    {
        $fallback = '';

        foreach ($answersLabeled as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $label = $this->normalizeText((string) ($answer['label'] ?? ''));
            $value = trim((string) ($answer['value'] ?? ''));

            if ($value === '' || !$this->looksLikeEmail($value)) {
                continue;
            }

            if (
                str_contains($label, 'email')
                || str_contains($label, 'e-mail')
                || str_contains($label, 'mail')
                || str_contains($label, 'courriel')
            ) {
                return $value;
            }

            if ($fallback === '') {
                $fallback = $value;
            }
        }

        return $fallback;
    }

    private function findPersonLikeAnswer(array $answersLabeled): string
    {
        foreach ($answersLabeled as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $label = $this->normalizeText((string) ($answer['label'] ?? ''));
            $value = trim((string) ($answer['value'] ?? ''));

            if ($value === '' || $this->looksLikeEmail($value) || $this->looksLikeWorkflowAnswer($value)) {
                continue;
            }

            if (str_contains($label, 'fonction') || str_contains($label, 'role') || str_contains($label, 'rôle')) {
                continue;
            }

            if (preg_match('/^[\p{L}\p{M}\'’.\- ]{3,80}$/u', $value) && preg_match('/\s/u', $value)) {
                $normalized = $this->normalizeText($value);

                foreach (['oui', 'non', 'partiellement', 'intermediaire', 'debutant', 'avance', 'cabinet', 'societe', 'entreprise'] as $bad) {
                    if ($normalized === $bad || str_contains($normalized, $bad . ' ')) {
                        continue 2;
                    }
                }

                return $value;
            }
        }

        return '';
    }

    private function findBusinessLikeAnswer(array $answersLabeled): string
    {
        foreach ($answersLabeled as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $value = trim((string) ($answer['value'] ?? ''));

            if ($value === '' || $this->looksLikeEmail($value)) {
                continue;
            }

            $normalized = $this->normalizeText($value);

            foreach ([
                'cabinet d avocat',
                'cabinet davocat',
                'avocats',
                'juridique',
                'droit',
                'conseil',
                'expertise',
                'comptable',
                'industrie',
                'association',
                'collectivite',
                'direction',
            ] as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $value;
                }
            }
        }

        return '';
    }

    private function looksLikeEmail(string $value): bool
    {
        return filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false;
    }

    private function looksLikeWorkflowAnswer(string $value): bool
    {
        $normalized = $this->normalizeText($value);

        foreach ([
            'a definir',
            'suite du projet',
            'rendez-vous',
            'rendez vous',
            'echange',
            'nda',
            'en discussion',
            'besoin d accompagnement',
            'accompagnement',
        ] as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function findAnswer(array $answersLabeled, array $keywords, array $excludeKeywords = []): string
    {
        foreach ($answersLabeled as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $label = $this->normalizeText((string) ($answer['label'] ?? ''));
            $value = trim((string) ($answer['value'] ?? ''));

            if ($value === '') {
                continue;
            }

            foreach ($excludeKeywords as $exclude) {
                if ($exclude !== '' && str_contains($label, $this->normalizeText((string) $exclude))) {
                    continue 2;
                }
            }

            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($label, $this->normalizeText((string) $keyword))) {
                    return $value;
                }
            }
        }

        return '';
    }

    private function normalizeText(string $value): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);

        $search = ['à','â','ä','á','ã','å','ç','é','è','ê','ë','í','ì','î','ï','ñ','ó','ò','ô','ö','õ','ú','ù','û','ü','ý','ÿ'];
        $replace = ['a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y'];

        return str_replace($search, $replace, $value);
    }

    private function clearStore(FlatFile $store): void
    {
        $items = $store->all();

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (is_array($item) && isset($item['id'])) {
                $store->delete((string) $item['id']);
            }
        }
    }
}
