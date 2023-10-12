<?php

namespace App\Admin;

use App\Entity\Administrator;
use App\Service\UserManager;
use LogicException;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AdministratorAdmin extends AbstractAdmin
{
    private readonly UserManager $userManager;

    public function setUserManager(UserManager $userManager): void
    {
        $this->userManager = $userManager;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('email', null, ['label' => 'Adresse email'])
            ->add('enabled', null, [
                'label' => 'Statut actif ?',
            ])
            ->add('lastLoginAt', null, [
                'pattern' => 'dd/MM/yyyy HH:mm:ss',
                'locale' => 'fr',
                'timezone' => 'Europe/Paris',
                'label' => 'Dernière connexion',
            ])
            ->add('createdAt', null, [
                'pattern' => 'dd/MM/yyyy',
                'locale' => 'fr',
                'timezone' => 'Europe/Paris',
                'label' => 'Date de création',
            ])
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
            ->with('Informations générales', ['class' => 'col-12 col-md-6'])
                ->add('email', EmailType::class, ['label' => 'Adresse email de l\'utilisateur'])
                ->add('lastLoginAt', null, [
                    'widget' => 'single_text',
                    'label' => 'Dernière connexion',
                    'format' => DateTimeType::DEFAULT_TIME_FORMAT,
                    'disabled' => true,
                    'html5' => false,
                ])
                ->add('createdAt', null, [
                    'widget' => 'single_text',
                    'label' => 'Date de création',
                    'format' => DateTimeType::DEFAULT_DATE_FORMAT,
                    'disabled' => true,
                    'html5' => false,
                ])
                ->add('updatedAt', null, [
                    'widget' => 'single_text',
                    'label' => 'Date de dernière modification',
                    'format' => DateTimeType::DEFAULT_DATE_FORMAT,
                    'disabled' => true,
                    'html5' => false,
                ])
            ->end()
            ->with('Sécurité', [
                'class' => 'col-12 col-md-6',
                'box_class' => 'box box-solid box-danger',
            ])
                ->add('plainPassword', TextType::class, [
                    'label' => 'Mot de passe',
                    'required' => $this->isCreationForm(),
                ])
                ->add('enabled', null, [
                    'label' => 'Statut actif ?',
                ])
                ->add('roles', CollectionType::class, [
                    'label' => 'Rôle(s)',
                    'disabled' => true,
                ])
            ->end()
        ;
    }

    protected function configureFormOptions(array &$formOptions): void
    {
        parent::configureFormOptions($formOptions);

        if ($this->isCreationForm()) {
            $formOptions['validation_groups'] = ['Default', 'CreateAdministrator'];
        } else {
            $formOptions['validation_groups'] = ['Default', 'UpdateAdministrator'];
        }
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Informations générales', ['class' => 'col-12 col-md-6'])
                ->add('id', null, ['label' => 'Identifiant'])
                ->add('email', null, ['label' => 'Adresse email'])
                ->add('lastLoginAt', null, ['label' => 'Dernière connexion'])
                ->add('createdAt', null, ['label' => 'Date de création'])
                ->add('updatedAt', null, ['label' => 'Date de dernière modification'])
            ->end()
            ->with('Sécurité', [
                'class' => 'col-12 col-md-6',
                'box_class' => 'box box-solid box-danger',
            ])
                ->add('enabled', null, ['label' => 'Statut actif ?'])
                ->add('roles', FieldDescriptionInterface::TYPE_ARRAY, [
                    'label' => 'Rôle(s)',
                    'display' => 'values',
                    'inline' => false,
                ])
            ->end()

        ;
    }

    public function preUpdate($object): void
    {
        if (!$object instanceof Administrator) {
            throw new LogicException();
        }
        $this->userManager->updatePassword($object);
    }

    public function prePersist($object): void
    {
        if (!$object instanceof Administrator) {
            throw new LogicException();
        }
        $this->userManager->updatePassword($object);
    }

    private function isCreationForm(): bool
    {
        return !$this->hasSubject() || !$this->getSubject() instanceof Administrator || !$this->getSubject()->getId();
    }
}
