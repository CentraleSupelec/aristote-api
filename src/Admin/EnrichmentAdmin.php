<?php

namespace App\Admin;

use App\Entity\Enrichment;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EnrichmentAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id', null, ['label' => 'ID'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('endUserIdentifier', null, ['label' => 'Utilisateur'])
            ->add('aiModel', null, ['label' => 'Modèle IA'])
            ->add('infrastructure', null, ['label' => 'Infrastructure'])
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
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Enrichment::getPossibleStatuses(),
            ])
            ->add('endUserIdentifier', null, ['label' => 'Utilisateur'])
            ->add('aiModel', null, ['label' => 'Modèle IA'])
            ->add('infrastructure', null, ['label' => 'Infrastructure'])
            ->add('language', null, ['label' => 'Langue'])
            ->add('translateTo', null, ['label' => 'Traduire en'])
            ->add('retries', null, ['label' => 'Essais'])
            ->add('generateMetadata', null, ['label' => 'Génération de métadonnées'])
            ->add('generateQuiz', null, ['label' => 'Génération de quiz'])
            ->add('generateNotes', null, ['label' => 'Prise de notes'])
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id', TextType::class, ['label' => 'ID'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('endUserIdentifier', null, ['label' => 'Utilisateur'])
            ->add('aiModel', null, ['label' => 'Modèle IA'])
            ->add('infrastructure', null, ['label' => 'Infrastructure'])
            ->add('language', null, ['label' => 'Langue'])
            ->add('translateTo', null, ['label' => 'Traduire en'])
            ->add('retries', null, ['label' => 'Essais'])
            ->add('generateMetadata', null, ['label' => 'Génération de métadonnées'])
            ->add('generateQuiz', null, ['label' => 'Génération de quiz'])
            ->add('generateNotes', null, ['label' => 'Prise de notes'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('status')
            ->add('endUserIdentifier')
            ->add('aiModel')
            ->add('infrastructure')
            ->add('language')
            ->add('translateTo')
        ;
    }
}
