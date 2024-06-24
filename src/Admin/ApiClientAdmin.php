<?php

namespace App\Admin;

use App\Constants;
use App\Entity\AiModel;
use App\Entity\ApiClient;
use App\Entity\Infrastructure;
use App\Service\ApiClientManager;
use League\Bundle\OAuth2ServerBundle\OAuth2Grants;
use LogicException;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ApiClientAdmin extends AbstractAdmin
{
    private ApiClientManager $apiClientManager;

    protected function alterNewInstance(object $object): void
    {
        if ($this->isCreationForm() && $object instanceof ApiClient) {
            $object->setActive(false);
            $object->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS]);
            $object->setFormExposedScopes([Constants::SCOPE_DEFAULT]);
        }
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('identifier', null, ['label' => 'Identifiant'])
            ->add('name', null, ['label' => 'Nom'])
            ->add('active', null, [
                'label' => 'Statut actif ?',
            ])
            ->add('tokenLastRequestedAt', null, [
                'pattern' => 'dd/MM/yyyy HH:mm:ss',
                'locale' => 'fr',
                'timezone' => 'Europe/Paris',
                'label' => 'Date de dernière requête du token',
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
                ->add('identifier', TextType::class, [
                    'label' => 'Identifiant du client',
                    'disabled' => !$this->isCreationForm(),
                ])
                ->add('name', TextType::class, ['label' => 'Nom du client'])
                ->add('tokenLastRequestedAt', null, [
                    'widget' => 'single_text',
                    'label' => 'Date de dernière requête de token',
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
                ->add('plainSecret', TextType::class, [
                    'label' => 'Secret',
                    'required' => $this->isCreationForm(),
                ])
                ->add('active', null, [
                    'label' => 'Statut actif ?',
                ])
                ->add('formExposedScopes', ChoiceType::class, [
                    'label' => 'Scope(s)',
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => Constants::getAvailableScopes(),
                ])
                ->add('formExposedGrants', ChoiceType::class, [
                    'label' => 'Grant(s)',
                    'disabled' => true,
                    'multiple' => true,
                    'choices' => Constants::getAvailableGrants(),
                ])
            ->end()
            ->with("Pour les workers d'enrichissment", [
                'class' => 'col-12 col-md-6',
                'box_class' => 'box box-solid box-danger',
            ])
                ->add('aiModel', EntityType::class, [
                    'label' => 'Modèle IA',
                    'class' => AiModel::class,
                    'required' => false,
                    'multiple' => false,
                ])
                ->add('infrastructure', EntityType::class, [
                    'label' => 'Infrastructure',
                    'class' => Infrastructure::class,
                    'required' => false,
                    'multiple' => false,
                ])
                ->add('treatUnspecifiedModelOrInfrastructure', null, [
                    'label' => "Prendre les enrichissements qui n'ont pas spécifié de modèle ou d'infrastructure",
                ])
            ->end()
        ;
    }

    protected function configureFormOptions(array &$formOptions): void
    {
        parent::configureFormOptions($formOptions);

        if ($this->isCreationForm()) {
            $formOptions['validation_groups'] = ['Default', 'CreateApiClient'];
        }
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Informations générales', ['class' => 'col-12 col-md-6'])
                ->add('identifier', null, ['label' => 'Identifiant'])
                ->add('name', null, ['label' => 'Nom'])
                ->add('tokenLastRequestedAt', null, ['label' => 'Date de dernière requête du token'])
                ->add('createdAt', null, ['label' => 'Date de création'])
                ->add('updatedAt', null, ['label' => 'Date de dernière modification'])
            ->end()
            ->with('Sécurité', [
                'class' => 'col-12 col-md-6',
                'box_class' => 'box box-solid box-danger',
            ])
                ->add('active', null, ['label' => 'Statut actif ?'])
                ->add('scopes', FieldDescriptionInterface::TYPE_ARRAY, [
                    'label' => 'Scope(s)',
                    'display' => 'values',
                    'inline' => false,
                ])
                ->add('grants', FieldDescriptionInterface::TYPE_ARRAY, [
                    'label' => 'Grant(s)',
                    'display' => 'values',
                    'inline' => false,
                ])

            ->end()
            ->with("Pour les workers d'enrichissment", [
                'class' => 'col-12 col-md-6',
                'box_class' => 'box box-solid box-danger',
            ])
                ->add('aiModel', EntityType::class, [
                    'label' => "Modèle IA (si Worker d'enrichissment)",
                    'class' => AiModel::class,
                    'required' => false,
                    'multiple' => false,
                ])
                ->add('infrastructure', EntityType::class, [
                    'label' => "Infrastructure (si Worker d'enrichissment)",
                    'class' => Infrastructure::class,
                    'required' => false,
                    'multiple' => false,
                ])
                ->add('treatUnspecifiedModelOrInfrastructure', null, [
                    'label' => "Prendre les enrichissements qui n'ont pas spécifié de modèle ou d'infrastructure (si Worker d'enrichissment)",
                ])
            ->end()
        ;
    }

    public function setApiClientManager(ApiClientManager $apiClientManager): void
    {
        $this->apiClientManager = $apiClientManager;
    }

    public function preUpdate($object): void
    {
        if (!$object instanceof ApiClient) {
            throw new LogicException();
        }

        $this->apiClientManager->updateApiClientSecret($object);
    }

    public function prePersist($object): void
    {
        if (!$object instanceof ApiClient) {
            throw new LogicException();
        }

        $this->apiClientManager->updateApiClientSecret($object);
    }

    private function isCreationForm(): bool
    {
        return !$this->hasSubject() || !$this->getSubject() instanceof ApiClient || !$this->getSubject()->getId();
    }
}
