<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CronSitemapsCommand extends ContainerAwareCommand {

	private $exportedVideosIdentifiers;

	protected function configure() {
		$this
			->setName('ladb:cron:sitemaps')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate sitemaps')
			->setHelp(<<<EOT
The <info>ladb:cron:sitemaps</info> generate sitemaps
EOT
			);
	}

	/////

	private function _flagVideoAsExported($kind, $embedIdentifer) {
		$this->exportedVideosIdentifiers[$kind.$embedIdentifer] = true;
	}

	private function _isVideoAsExported($kind, $embedIdentifer) {
		if (isset($this->exportedVideosIdentifiers[$kind.$embedIdentifer])) {
			return $this->exportedVideosIdentifiers[$kind.$embedIdentifer];
		}
		return false;
	}

	/////

	private function _getEntitySitemap($entityClassName, $section) {
		$om = $this->getContainer()->get('doctrine')->getManager();
		$entityRepository = $om->getRepository($entityClassName);
		$lastCreatedEntity = $entityRepository->findLastCreated();
		$lastUpdatedEntity = $entityRepository->findLastUpdated();
		$createdAt = !is_null($lastCreatedEntity) ? $lastCreatedEntity->getCreatedAt() : null;
		$updatedAt = !is_null($lastUpdatedEntity) ? $lastUpdatedEntity->getUpdatedAt() : null;
		if (!is_null($createdAt) && $createdAt > $updatedAt) {
			$lastmod = $createdAt->format('Y-m-d\TH:i:sP');
		} else if (!is_null($updatedAt)) {
			$lastmod = $updatedAt->format('Y-m-d\TH:i:sP');
		} else {
			$lastmod = date_format(new \DateTime(), 'Y-m-d\TH:i:sP');
		}
		return array(
			'loc'     => $this->getContainer()->get('assets.packages')->getUrl('/sitemap-'.$section.'.xml'),
			'lastmod' => $lastmod,
		);
	}

	private function _getEntityUrls($entityClassName, $entityName, $forced, $verbose, OutputInterface $output, $slugged = true) {
		$router = $this->getContainer()->get('router');
		$om = $this->getContainer()->get('doctrine')->getManager();
		$entityRepository = $om->getRepository($entityClassName);
		$picturedUtils = $this->getContainer()->get(PicturedUtils::NAME);
		$videoHostingUtils = $this->getContainer()->get(VideoHostingUtils::NAME);

		$urls = array();
		$entities = $entityRepository->findAll();

		$progress = new ProgressBar($output, count($entities));
		$progress->start();

		foreach ($entities as $entity) {

			$progress->advance();

			if ($entity instanceof IndexableInterface && !$entity->isIndexable()) {
				continue;
			}

			// Images & Videos
			$images = array();
			$videos = array();
			if ($entity instanceof MultiPicturedInterface) {
				foreach ($entity->getPictures() as $picture) {
					$image = $picturedUtils->getPictureSitemapData($picture);
					if (!is_null($image)) {
						$images[] = $image;
					}
				}
			}
			if ($entity instanceof BlockBodiedInterface) {
				foreach ($entity->getBodyBlocks() as $block) {
					if ($block instanceof \Ladb\CoreBundle\Entity\Block\Video) {
						$video = $videoHostingUtils->getVideoSitemapData($block->getKind(), $block->getEmbedIdentifier());
						if (!is_null($video) && !$this->_isVideoAsExported($block->getKind(), $block->getEmbedIdentifier())) {
							$videos[] = $video;
							$this->_flagVideoAsExported($block->getKind(), $block->getEmbedIdentifier());
						}
					}
					if ($block instanceof \Ladb\CoreBundle\Entity\Block\Gallery) {
						foreach ($block->getPictures() as $picture) {
							$image = $picturedUtils->getPictureSitemapData($picture);
							if (!is_null($image)) {
								$images[] = $image;
							}
						}
					}
				}
			}
			if ($entity instanceof \Ladb\CoreBundle\Entity\Find\Find) {
				if ($entity->getContent() instanceof \Ladb\CoreBundle\Entity\Find\Content\Video) {
					$video = $videoHostingUtils->getVideoSitemapData($entity->getContent()->getKind(), $entity->getContent()->getEmbedIdentifier());
					if (!is_null($video) && !$this->_isVideoAsExported($entity->getContent()->getKind(), $entity->getContent()->getEmbedIdentifier())) {
						$videos[] = $video;
						$this->_flagVideoAsExported($entity->getContent()->getKind(), $entity->getContent()->getEmbedIdentifier());
					}
				}
			}
			if ($entity instanceof \Ladb\CoreBundle\Entity\Howto\Howto) {
				foreach ($entity->getArticles() as $article) {
					foreach ($article->getBodyBlocks() as $block) {
						if ($block instanceof \Ladb\CoreBundle\Entity\Block\Video) {
							$video = $videoHostingUtils->getVideoSitemapData($block->getKind(), $block->getEmbedIdentifier());
							if (!is_null($video) && !$this->_isVideoAsExported($block->getKind(), $block->getEmbedIdentifier())) {
								$videos[] = $video;
								$this->_flagVideoAsExported($block->getKind(), $block->getEmbedIdentifier());
							}
						}
						if ($block instanceof \Ladb\CoreBundle\Entity\Block\Gallery) {
							foreach ($block->getPictures() as $picture) {
								$image = $picturedUtils->getPictureSitemapData($picture);
								if (!is_null($image)) {
									$images[] = $image;
								}
							}
						}
					}
				}
			}

			$urls[] = array(
				'loc'        => $router->generate('core_'.$entityName.'_show', $slugged ? array('id' => $entity->getSluggedId()) : array( 'username' => $entity->getUsernameCanonical() ), UrlGeneratorInterface::ABSOLUTE_URL),
				'lastmod'    => is_null($entity->getUpdatedAt()) ? $entity->getCreatedAt()->format('Y-m-d\TH:i:sP') : $entity->getUpdatedAt()->format('Y-m-d\TH:i:sP'),
				'changefreq' => 'daily',
				'images'     => $images,
				'videos'     => $videos,
			);
		}

		$progress->finish();

		return $urls;
	}

	private function _createSitemapFile($entityClassName, $entityName, $section, $forced, $verbose, OutputInterface $output, $slugged = true) {
		$templating = $this->getContainer()->get('templating');

		if ($verbose) {
			$output->writeln('<info>Building '.$section.' sitemap...</info>');
		}

		$urls = $this->_getEntityUrls($entityClassName, $entityName, $forced, $verbose, $output, $slugged);
		$data = $templating->render('LadbCoreBundle:Command:_cron-sitemap-entities.xml.twig', array(
			'urls' => $urls,
		));

		unset($urls);

		if ($forced) {

			$filename = dirname(__FILE__).'/../../../../web/sitemap-'.$section.'.xml';

			if ($verbose) {
				$output->write('<info> -> Wrinting '.$filename.' file...</info>');
			}

			$fp = fopen($filename, 'w');
			fwrite($fp, $data);
			fclose($fp);

			if ($verbose) {
				$output->writeln('<comment> [Done]</comment>');
			}
		} else {
			if ($verbose) {
				$output->writeln('<comment> [Fake]</comment>');
			}
		}

	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$this->exportedVideosIdentifiers = array();

		$defs = array(
			array(
				'className' => \Ladb\CoreBundle\Entity\Wonder\Creation::CLASS_NAME,
				'name'      => 'creation',
				'section'   => 'creations',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Wonder\Plan::CLASS_NAME,
				'name'      => 'plan',
				'section'   => 'plans',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Wonder\Workshop::CLASS_NAME,
				'name'      => 'workshop',
				'section'   => 'workshops',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Howto\Howto::CLASS_NAME,
				'name'      => 'howto',
				'section'   => 'howtos',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Find\Find::CLASS_NAME,
				'name'      => 'find',
				'section'   => 'finds',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Blog\Post::CLASS_NAME,
				'name'      => 'blog_post',
				'section'   => 'posts',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Faq\Question::CLASS_NAME,
				'name'      => 'faq_question',
				'section'   => 'questions',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Knowledge\Wood::CLASS_NAME,
				'name'      => 'wood',
				'section'   => 'woods',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\Knowledge\Provider::CLASS_NAME,
				'name'      => 'provider',
				'section'   => 'providers',
				'slugged'   => true,
			),
			array(
				'className' => \Ladb\CoreBundle\Entity\User::CLASS_NAME,
				'name'      => 'user',
				'section'   => 'users',
				'slugged'   => false,
			),
		);


		/////

		foreach ($defs as $def) {
			$this->_createSitemapFile($def['className'], $def['name'], $def['section'], $forced, $verbose, $output, $def['slugged']);
		}

		/////

		$templating = $this->getContainer()->get('templating');

		if ($verbose) {
			$output->write('<info>Building index sitemap...</info>');
		}

		$sitemaps = array();

		foreach ($defs as $def) {
			$sitemaps[] = $this->_getEntitySitemap($def['className'], $def['section']);
		}

		$data = $templating->render('LadbCoreBundle:Command:_cron-sitemap-index.xml.twig', array(
			'sitemaps' => $sitemaps,
		));

		if ($forced) {

			$filename = dirname(__FILE__).'/../../../../web/sitemap-index.xml';

			if ($verbose) {
				$output->write('<info> -> Wrinting '.$filename.' file...</info>');
			}

			$fp = fopen($filename, 'w');
			fwrite($fp, $data);
			fclose($fp);

			if ($verbose) {
				$output->writeln('<comment> [Done]</comment>');
			}
		} else {
			if ($verbose) {
				$output->writeln('<comment> [Fake]</comment>');
			}
		}


	}

}