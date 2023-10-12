<?php

namespace App\DataFixtures;

use App\Entity\ApiClient;
use App\Entity\Choice;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Tag;
use App\Entity\Topic;
use App\Entity\Transcript;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class EnrichmentFixturesProvider
{
    public static function generateEnrichment(ApiClient $apiClient, ?EntityManagerInterface $entityManager = null): Enrichment
    {
        $enrichmentVersionV1 = self::getEnrichmentVersion($entityManager, true);
        $enrichmentVersionV2 = self::getEnrichmentVersion($entityManager, false);
        $enrichmentVersionV3 = self::getEnrichmentVersion($entityManager, false);
        $enrichmentVersionV4 = self::getEnrichmentVersion($entityManager, false);
        $enrichmentVersions = [
            $enrichmentVersionV1,
            $enrichmentVersionV2,
            $enrichmentVersionV3,
            $enrichmentVersionV4,
        ];

        $enrichment = (new Enrichment())
            ->setCreatedBy($apiClient)
            ->setStatus(Enrichment::STATUS_PENDING)
        ;

        $dateTime = new DateTime('2023-10-03 00:00:00');

        foreach ($enrichmentVersions as $enrichmentVersion) {
            $enrichmentVersion->setCreatedAt($dateTime)->setUpdatedAt($dateTime);
            $enrichment->addVersion($enrichmentVersion);
            $dateTime->add(DateInterval::createFromDateString('1 day'));
        }

        if (null !== $entityManager) {
            $entityManager->persist($enrichment);

            $entityManager->flush();
        }

        return $enrichment;
    }

    public static function getEnrichmentVersion(?EntityManagerInterface $entityManager = null, bool $initialVersion = false): EnrichmentVersion
    {
        $enrichmentVersionMetadata = self::getEnrichmentVersionMetadata($entityManager);
        $transcript = self::getTranscript($entityManager);
        $multipleChoiceQuestions = self::getMultipleChoiceQuestions($entityManager);

        $enrichmentVersion = (new EnrichmentVersion())
            ->setInitialVersion($initialVersion)
            ->setEnrichmentVersionMetadata($enrichmentVersionMetadata)
            ->setTranscript($transcript)
        ;

        foreach ($multipleChoiceQuestions as $multipleChoiceQuestion) {
            $enrichmentVersion->addMultipleChoiceQuestion($multipleChoiceQuestion);
        }

        if (null !== $entityManager) {
            $entityManager->persist($enrichmentVersion);

            $entityManager->flush();
        }

        return $enrichmentVersion;
    }

    public static function getEnrichmentVersionMetadata(?EntityManagerInterface $entityManager = null): EnrichmentVersionMetadata
    {
        $enrichmentVersionMetadata = (new EnrichmentVersionMetadata())
            ->setDescription('Enrichment Version Metadata Description')
            ->setTitle('Enrichment Version Metadata Title')
            ->addTag((new Tag())->setText('Physiques'))
            ->addTag((new Tag())->setText('Mathématiques'))
            ->addTopic((new Topic())->setText('Relativité'))
            ->addTopic((new Topic())->setText('Dérivé'))
        ;

        if (null !== $entityManager) {
            $entityManager->persist($enrichmentVersionMetadata);

            $entityManager->flush();
        }

        return $enrichmentVersionMetadata;
    }

    public static function getTranscript(?EntityManagerInterface $entityManager = null): Transcript
    {
        $sentences = self::getSentences();
        $transcript = (new Transcript())
            ->setLanguage('fr')
            ->setOriginalFilename('video.mp4')
            ->setSentences($sentences)
        ;

        if (null !== $entityManager) {
            $entityManager->persist($transcript);

            $entityManager->flush();
        }

        return $transcript;
    }

    public static function getSentences(): string
    {
        return json_encode([
            '0' => [
                'is_transient' => true,
                'no_speech_prob' => 0,
                'start' => '1,25',
                'end' => '3,70',
                'text' => 'Une phrase simple',
            ],
            '1' => [
                'is_transient' => true,
                'no_speech_prob' => 1,
                'start' => '4,65',
                'end' => '6,30',
                'text' => 'Une autre phrase',
            ],
        ]);
    }

    public static function getMultipleChoiceQuestions(?EntityManagerInterface $entityManager = null): array
    {
        $firstMCQFirstChoice = (new Choice())
            ->setOptionText('M=Ec2')
        ;

        $firstMCQSecondChoice = (new Choice())
            ->setOptionText('E=Mc2')
            ->setCorrectAnswer(true)
        ;

        $firstMultipleChoiceQuestion = (new MultipleChoiceQuestion())
            ->setQuestion('Quelle est la bonne équation ?')
            ->setExplanation("C'est la formule de la relativité restreinte")
            ->addChoice($firstMCQFirstChoice)
            ->addChoice($firstMCQSecondChoice)
        ;

        $secondMCQFirstChoice = (new Choice())
            ->setOptionText("f'(x) = x")
        ;

        $secondMCQSecondChoice = (new Choice())
            ->setOptionText("f'(x) = 2")
            ->setCorrectAnswer(true)
        ;

        $secondMultipleChoiceQuestion = (new MultipleChoiceQuestion())
            ->setQuestion('Quelle est la dérivé de f(x) = 2x ?')
            ->setExplanation("Si f(x) = ax avec a une constante, alors f'(x) = a")
            ->addChoice($secondMCQFirstChoice)
            ->addChoice($secondMCQSecondChoice)
        ;

        if (null !== $entityManager) {
            $entityManager->persist($firstMultipleChoiceQuestion);
            $entityManager->persist($secondMultipleChoiceQuestion);

            $entityManager->flush();
        }

        return [$firstMultipleChoiceQuestion, $secondMultipleChoiceQuestion];
    }
}
