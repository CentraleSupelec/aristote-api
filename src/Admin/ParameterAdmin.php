<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class ParameterAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('name', null, ['label' => 'Paramètre'])
            ->add('description', null, ['label' => 'Description'])
            ->add('value', null, ['label' => 'Valeur'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => ['template' => 'sonata/list__action_delete_custom.html.twig'],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', null, ['label' => 'Paramètre'])
            ->add('description', null, ['label' => 'Description'])
            ->add('value', null, ['label' => 'Valeur'])
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('name', null, ['label' => 'Paramètre'])
            ->add('description', null, ['label' => 'Description'])
            ->add('value', null, ['label' => 'Valeur'])
        ;
    }
}
