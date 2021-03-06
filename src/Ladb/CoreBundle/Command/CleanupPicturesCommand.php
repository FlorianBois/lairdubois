<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupPicturesCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:pictures')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup pictures')
			->setHelp(<<<EOT
The <info>ladb:cleanup:pictures</info> command remove unused pictures
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Extract pictures /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p' ))
			->from('LadbCoreBundle:Picture', 'p')
		;

		try {
			$pictures = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$pictures = array();
		}

		$pictureCounters = array();
		foreach ($pictures as $picture) {
			$pictureCounters[$picture->getId()] = array( $picture, 0 );
		}

		// Check Comments /////

		$output->writeln('<info>Checking comments...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'ps' ))
			->from('LadbCoreBundle:Comment', 'c')
			->leftJoin('c.pictures', 'ps')
		;

		try {
			$comments = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$comments = array();
		}

		foreach ($comments as $comment) {
			foreach ($comment->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($comments);

		// Check Messages /////

		$output->writeln('<info>Checking messages...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'm', 'ps' ))
			->from('LadbCoreBundle:Message\Message', 'm')
			->leftJoin('m.pictures', 'ps')
		;

		try {
			$messages = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$messages = array();
		}

		foreach ($messages as $message) {
			foreach ($message->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($messages);

		// Check avatars and banners /////

		$output->writeln('<info>Checking avatars and banners...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'u', 'a', 'b' ))
			->from('LadbCoreBundle:User', 'u')
			->leftJoin('u.avatar', 'a')
			->leftJoin('u.banner', 'b')
		;

		try {
			$users = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$users = array();
		}

		foreach ($users as $user) {
			$avatar = $user->getAvatar();
			if (!is_null($avatar)) {
				$pictureCounters[$avatar->getId()][1]++;
			}
			$banner = $user->getBanner();
			if (!is_null($banner)) {
				$pictureCounters[$banner->getId()][1]++;
			}
		}
		unset($users);

		// Check creations /////

		$output->writeln('<info>Checking creations...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'mp', 'ps' ))
			->from('LadbCoreBundle:Wonder\Creation', 'c')
			->leftJoin('c.mainPicture', 'mp')
			->leftJoin('c.pictures', 'ps')
		;

		try {
			$creations = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$creations = array();
		}

		foreach ($creations as $creation) {
			$mainPicture = $creation->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$sticker = $creation->getSticker();
			if (!is_null($sticker)) {
				$pictureCounters[$sticker->getId()][1]++;
			}
			$strip = $creation->getStrip();
			if (!is_null($strip)) {
				$pictureCounters[$strip->getId()][1]++;
			}
			foreach ($creation->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($creations);

		// Check plans /////

		$output->writeln('<info>Checking plans...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp', 'ps' ))
			->from('LadbCoreBundle:Wonder\Plan', 'p')
			->leftJoin('p.mainPicture', 'mp')
			->leftJoin('p.pictures', 'ps')
		;

		try {
			$plans = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$plans = array();
		}

		foreach ($plans as $plan) {
			$mainPicture = $plan->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$sticker = $plan->getSticker();
			if (!is_null($sticker)) {
				$pictureCounters[$sticker->getId()][1]++;
			}
			$strip = $plan->getStrip();
			if (!is_null($strip)) {
				$pictureCounters[$strip->getId()][1]++;
			}
			foreach ($plan->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($plans);

		// Check workshops /////

		$output->writeln('<info>Checking workshops...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'mp', 'ps' ))
			->from('LadbCoreBundle:Wonder\Workshop', 'w')
			->leftJoin('w.mainPicture', 'mp')
			->leftJoin('w.pictures', 'ps')
		;

		try {
			$workshops = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$workshops = array();
		}

		foreach ($workshops as $workshop) {
			$mainPicture = $workshop->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$sticker = $workshop->getSticker();
			if (!is_null($sticker)) {
				$pictureCounters[$sticker->getId()][1]++;
			}
			$strip = $workshop->getStrip();
			if (!is_null($strip)) {
				$pictureCounters[$strip->getId()][1]++;
			}
			foreach ($workshop->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($workshops);

		// Check Finds /////

		$output->writeln('<info>Checking finds...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'c' ))
			->from('LadbCoreBundle:Find\Find', 'f')
			->leftJoin('f.content', 'c')
		;

		try {
			$finds = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$finds = array();
		}

		foreach ($finds as $find) {
			$mainPicture = $find->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$content = $find->getContent();
			if ($content instanceof \Ladb\CoreBundle\Entity\Find\Content\Gallery) {
				foreach ($content->getPictures() as $picture) {
					$pictureCounters[$picture->getId()][1]++;
				}
			}
		}
		unset($finds);

		// Check howtos and articles /////

		$output->writeln('<info>Checking howtos...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'h', 'a', 'mp', 's' ))
			->from('LadbCoreBundle:Howto\Howto', 'h')
			->leftJoin('h.articles', 'a')
			->leftJoin('h.mainPicture', 'mp')
			->leftJoin('h.sticker', 's')
		;

		try {
			$howtos = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$howtos = array();
		}

		foreach ($howtos as $howto) {
			$mainPicture = $howto->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$sticker = $howto->getSticker();
			if (!is_null($sticker)) {
				$pictureCounters[$sticker->getId()][1]++;
			}
			foreach ($howto->getArticles() as $article) {
				$sticker = $article->getSticker();
				if (!is_null($sticker)) {
					$pictureCounters[$sticker->getId()][1]++;
				}
			}
		}
		unset($howtos);

		// Check blog posts /////

		$output->writeln('<info>Checking blog posts...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp' ))
			->from('LadbCoreBundle:Blog\Post', 'p')
			->leftJoin('p.mainPicture', 'mp')
		;

		try {
			$posts = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$posts = array();
		}

		foreach ($posts as $post) {
			$mainPicture = $post->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
		}
		unset($posts);

		// Check Woods /////

		$output->writeln('<info>Checking woods...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'mp', 'eg', 'lb', 'tr', 'le', 'br' ))
			->from('LadbCoreBundle:Knowledge\Wood', 'w')
			->leftJoin('w.mainPicture', 'mp')
			->leftJoin('w.endgrain', 'eg')
			->leftJoin('w.lumber', 'lb')
			->leftJoin('w.tree', 'tr')
			->leftJoin('w.leaf', 'le')
			->leftJoin('w.bark', 'br')
		;

		try {
			$woods = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$woods = array();
		}

		foreach ($woods as $wood) {
			$mainPicture = $wood->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$endgrain = $wood->getEndgrain();
			if (!is_null($endgrain)) {
				$pictureCounters[$endgrain->getId()][1]++;
			}
			$lumber = $wood->getLumber();
			if (!is_null($lumber)) {
				$pictureCounters[$lumber->getId()][1]++;
			}
			$tree = $wood->getTree();
			if (!is_null($tree)) {
				$pictureCounters[$tree->getId()][1]++;
			}
			$leaf = $wood->getLeaf();
			if (!is_null($leaf)) {
				$pictureCounters[$leaf->getId()][1]++;
			}
			$bark = $wood->getLeaf();
			if (!is_null($bark)) {
				$pictureCounters[$bark->getId()][1]++;
			}
		}
		unset($woods);

		// Check Woods /////

		$output->writeln('<info>Checking Providers...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp', 'ph' ))
			->from('LadbCoreBundle:Knowledge\Provider', 'p')
			->leftJoin('p.mainPicture', 'mp')
			->leftJoin('p.photo', 'ph')
		;

		try {
			$providers = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$providers = array();
		}

		foreach ($providers as $provider) {
			$mainPicture = $provider->getMainPicture();
			if (!is_null($mainPicture)) {
				$pictureCounters[$mainPicture->getId()][1]++;
			}
			$photo = $provider->getPhoto();
			if (!is_null($photo)) {
				$pictureCounters[$photo->getId()][1]++;
			}
		}
		unset($providers);

		// Check Knowledge/Value/Pictures /////

		$output->writeln('<info>Checking knowledge value pictures...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'v', 'd' ))
			->from('LadbCoreBundle:Knowledge\Value\Picture', 'v')
			->leftJoin('v.data', 'd')
		;

		try {
			$values = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$values = array();
		}

		foreach ($values as $value) {
			$content = $value->getData();
			if (!is_null($content)) {
				$pictureCounters[$content->getId()][1]++;
			}
		}
		unset($values);

		// Check Block/Gallery /////

		$output->writeln('<info>Checking block galleries...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'g', 'ps' ))
			->from('LadbCoreBundle:Block\Gallery', 'g')
			->leftJoin('g.pictures', 'ps')
		;

		try {
			$galleries = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$galleries = array();
		}

		foreach ($galleries as $gallery) {
			foreach ($gallery->getPictures() as $picture) {
				$pictureCounters[$picture->getId()][1]++;
			}
		}
		unset($galleries);

		// Check Textures /////

		$output->writeln('<info>Checking textures...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'm' ))
			->from('LadbCoreBundle:Knowledge\Wood\Texture', 'm')
		;

		try {
			$textures = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$textures = array();
		}

		foreach ($textures as $texture) {
			if (!is_null($texture->getSinglePicture())) {
				$pictureCounters[$texture->getSinglePicture()->getId()][1]++;
			}
			if (!is_null($texture->getMosaicPicture())) {
				$pictureCounters[$texture->getMosaicPicture()->getId()][1]++;
			}
		}
		unset($textures);

		// Cleanup /////

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');
		$unusedPictureCount = 0;
		foreach ($pictureCounters as $pictureCounter) {
			$counter = $pictureCounter[1];
			if ($counter == 0) {
				$unusedPictureCount++;
				$picture = $pictureCounter[0];
				if ($verbose) {
					$output->writeln('<info> -> "'.$picture->getPath().'" is unused</info>');
				}
				if ($forced) {
					$om->remove($picture);
				}
			}
		}

		if ($forced) {
			if ($unusedPictureCount > 0) {
				$om->flush();
			}
			$output->writeln('<info>'.$unusedPictureCount.' pictures removed</info>');
		} else {
			$output->writeln('<info>'.$unusedPictureCount.' pictures to remove</info>');
		}
	}

}