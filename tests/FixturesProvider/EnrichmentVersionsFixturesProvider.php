<?php

namespace App\Tests\FixturesProvider;

use App\Entity\Choice;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class EnrichmentVersionsFixturesProvider
{
    public function __construct(
    ) {
    }

    public static function getEnrichmentVersion(?EntityManagerInterface $entityManager, ?Enrichment $enrichment = null): EnrichmentVersion
    {
        if (null === $enrichment) {
            $enrichment = EnrichmentFixturesProvider::getEnrichment($entityManager);
        }

        $enrichmentVersion = (new EnrichmentVersion())
            ->setCreatedAt(new DateTime())
            ->setInitialVersion(0 === $enrichment->getVersions()->count() ? true : false)
            ->setAiGenerated(true)
            ->setEnrichmentVersionMetadata(enrichmentVersionMetadata: EnrichmentVersionsFixturesProvider::getEnrichmentVersionMetadata($enrichment))
            ->setTranscript(EnrichmentVersionsFixturesProvider::getTranscript())
            ->addMultipleChoiceQuestion(EnrichmentVersionsFixturesProvider::getMultipleChoiceQuestion())
        ;

        $enrichment->addVersion($enrichmentVersion)->setStatus(Enrichment::STATUS_SUCCESS);

        if (null !== $entityManager) {
            $entityManager->persist($enrichment);
            $entityManager->flush();
        }

        return $enrichmentVersion;
    }

    public static function getEnrichmentVersionMetadata(Enrichment $enrichment): EnrichmentVersionMetadata
    {
        return (new EnrichmentVersionMetadata())
            ->setTitle('Test video')
            ->setDescription('This is a test video')
            ->setDiscipline($enrichment->getDisciplines()[0])
            ->setMediaType($enrichment->getMediaTypes()[1])
            ->setTopics(['Linear Algebra', 'Derivative', 'Calculus'])
        ;
    }

    public static function getTranscript(): Transcript
    {
        return (new Transcript())
            ->setLanguage('fr')
            ->setOriginalFilename('video.mp4')
            ->setText('Transcript')
            ->setSentences('[{"text": "Transcript", "start": 1, "end": 4}]')
        ;
    }

    public static function getMultipleChoiceQuestion(): MultipleChoiceQuestion
    {
        return (new MultipleChoiceQuestion())
            ->setQuestion('What is the derivate of x ?')
            ->setExplanation('x is of the form a.x (where a = 1), which derivate is a')
            ->addChoice((new Choice())
                ->setOptionText('1')
                ->setCorrectAnswer(true)
            )
            ->addChoice((new Choice())
                ->setOptionText('x')
                ->setCorrectAnswer(false)
            )
        ;
    }

    public static function addEnrichmentVersion(EntityManagerInterface $entityManager, Enrichment $enrichment): EnrichmentVersion
    {
        $enrichmentVersion = (new EnrichmentVersion())
            ->setCreatedAt(new DateTime())
            ->setInitialVersion(0 === $enrichment->getVersions()->count() ? true : false)
            ->setAiGenerated(true)
            ->setEnrichmentVersionMetadata(enrichmentVersionMetadata: EnrichmentVersionsFixturesProvider::getEnrichmentVersionMetadata($enrichment))
            ->setTranscript(EnrichmentVersionsFixturesProvider::getTranscript())
            ->addMultipleChoiceQuestion(EnrichmentVersionsFixturesProvider::getMultipleChoiceQuestion())
        ;

        $enrichment->addVersion($enrichmentVersion)->setStatus(Enrichment::STATUS_SUCCESS);

        if (null !== $entityManager) {
            $entityManager->persist($enrichment);
            $entityManager->flush();
        }

        return $enrichmentVersion;
    }
}
