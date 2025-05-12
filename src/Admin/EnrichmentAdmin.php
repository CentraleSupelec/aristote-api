<?php

namespace App\Admin;

use App\Constants;
use App\Entity\Enrichment;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EnrichmentAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', null, [
                'label' => 'ID',
                'template' => 'sonata/CRUD/enrichment_id.html.twig',
            ])
            ->add('media.originalFileName', null, ['label' => 'Nom du fichier'])
            ->add('createdBy', null, ['label' => 'Créé par', 'disabled' => true])
            ->add('status', null, ['label' => 'Statut'])
            ->add('endUserIdentifier', null, ['label' => 'Utilisateur'])
            ->add('aiModel', null, ['label' => 'Modèle IA'])
            ->add('infrastructure', null, ['label' => 'Infrastructure'])
            ->add('deleted', null, ['label' => 'Supprimé'])
            ->add('transcriptionRetries', null, ['label' => 'Essais de transcription'])
            ->add('enrichmentRetries', null, ['label' => "Essais d'enrichissement"])
            ->add('translationRetries', null, ['label' => 'Essais de traduction'])
            ->add('evaluationRetries', null, ['label' => "Essais d'évaluation"])
            ->add('media.duration', null, ['label' => 'Durée'])
            ->add('createdAt', null, ['label' => 'Créé le'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('id', TextType::class, ['label' => 'ID', 'disabled' => true])
            ->add('createdBy', TextType::class, ['label' => 'Créé par', 'disabled' => true])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Enrichment::getPossibleStatuses(),
            ])
            ->add('endUserIdentifier', null, ['label' => 'Utilisateur'])
            ->add('aiModel', null, ['label' => 'Modèle IA'])
            ->add('infrastructure', null, ['label' => 'Infrastructure'])
            ->add('language', null, ['label' => 'Langue'])
            ->add('translateTo', null, ['label' => 'Traduire en'])
            ->add('transcriptionRetries', null, ['label' => 'Essais de transcription'])
            ->add('enrichmentRetries', null, ['label' => "Essais d'enrichissement"])
            ->add('translationRetries', null, ['label' => 'Essais de traduction'])
            ->add('evaluationRetries', null, ['label' => "Essais d'évaluation"])
            ->add('priority', null, ['label' => 'Priorité'])
            ->add('generateMetadata', null, ['label' => 'Génération de métadonnées'])
            ->add('generateQuiz', null, ['label' => 'Génération de quiz'])
            ->add('generateNotes', null, ['label' => 'Prise de notes'])
            ->add('aiEvaluation', null, ['label' => 'Modèle IA pour évalutation'])
            ->add('failureCause', null, ['label' => "Cause d'erreur"])
            ->add('deleted', null, ['label' => 'Supprimé'])
            ->add('latestEnrichmentRequestedAt', null, ['label' => "Date de denière demande d'enrichissement"])
            ->add('contributors', CollectionType::class, [
                'label' => 'Contributeurs',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => TextType::class,
                'prototype' => true,
            ])
            ->add('notificationWebhookUrl', null, ['label' => 'URL de notification'])
            ->add('notificationLevel', ChoiceType::class, [
                'label' => 'Niveau de notification',
                'choices' => Constants::getNotificationLevels(),
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id', TextType::class, ['label' => 'ID'])
            ->add('createdBy', null, ['label' => 'Créé par'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('endUserIdentifier', null, ['label' => 'Utilisateur'])
            ->add('aiModel', null, ['label' => 'Modèle IA'])
            ->add('infrastructure', null, ['label' => 'Infrastructure'])
            ->add('language', null, ['label' => 'Langue'])
            ->add('transcriptionRetries', null, ['label' => 'Essais de transcription'])
            ->add('enrichmentRetries', null, ['label' => "Essais d'enrichissement"])
            ->add('translationRetries', null, ['label' => 'Essais de traduction'])
            ->add('evaluationRetries', null, ['label' => "Essais d'évaluation"])
            ->add('priority', null, ['label' => 'Priorité'])
            ->add('generateMetadata', null, ['label' => 'Génération de métadonnées'])
            ->add('generateQuiz', null, ['label' => 'Génération de quiz'])
            ->add('generateNotes', null, ['label' => 'Prise de notes'])
            ->add('createdAt', null, ['label' => 'Date de création'])
            ->add('latestEnrichmentRequestedAt', null, ['label' => "Date de denière demande d'enrichissement"])
            ->add('uploadStartedAt', null, ['label' => 'Début de téléversement'])
            ->add('uploadEndedAt', null, ['label' => 'Fin de téléversement'])
            ->add('transcribingStartedAt', null, ['label' => 'Début de transcription'])
            ->add('transcribingEndedAt', null, ['label' => 'Fin de transcription'])
            ->add('aiEnrichmentStartedAt', null, ['label' => "Début d'enrichissement"])
            ->add('aiEnrichmentEndedAt', null, ['label' => "Fin d'enrichissement"])
            ->add('translationStartedAt', null, ['label' => 'Début de traduction'])
            ->add('translationEndedAt', null, ['label' => 'Fin de traduction'])
            ->add('failureCause', null, ['label' => "Cause d'erreur"])
            ->add('mediaUrl', null, ['label' => 'Média URL'])
            ->add('notificationWebhookUrl', null, ['label' => 'URL de notification'])
            ->add('notificationLevel', null, ['label' => 'Niveau de notification'])
            ->add('media.originalFileName', null, ['label' => 'Nom du fichier'])
            ->add('deleted', null, ['label' => 'Supprimé'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('createdBy')
            ->add('status')
            ->add('endUserIdentifier')
            ->add('aiModel')
            ->add('infrastructure')
            ->add('language')
            ->add('translateTo')
            ->add('deleted')
            ->add('media.originalFileName')
        ;
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'createdAt';
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
    }
}
