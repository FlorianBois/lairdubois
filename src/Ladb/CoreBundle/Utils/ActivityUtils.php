<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;

class ActivityUtils {

	const NAME = 'ladb_core.activity_utils';

	protected $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	/////

	private function _deleteActivities($activities, $flush = true) {
		foreach ($activities as $activity) {
			$this->om->remove($activity);
		}
		if ($flush) {
			$this->om->flush();
		}
	}

	/////

	// Create /////

	public function createCommentActivity(\Ladb\CoreBundle\Entity\Comment $comment, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Comment();
		$activity->setUser($comment->getUser());
		$activity->setComment($comment);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createContributeActivity(\Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue $value, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Contribute();
		$activity->setUser($value->getUser());
		$activity->setValue($value);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createFollowActivity(\Ladb\CoreBundle\Entity\Follower $follower, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Follow();
		$activity->setUser($follower->getUser());
		$activity->setFollower($follower);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createLikeActivity(\Ladb\CoreBundle\Entity\Like $like, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Like();
		$activity->setUser($like->getUser());
		$activity->setLike($like);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createMentionActivity(\Ladb\CoreBundle\Entity\User $user, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Mention();
		$activity->setUser($user);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createPublishActivity(\Ladb\CoreBundle\Entity\User $user, $entityType, $entityId, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Publish();
		$activity->setUser($user);
		$activity->setEntityType($entityType);
		$activity->setEntityId($entityId);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createVoteActivity(\Ladb\CoreBundle\Entity\Vote $vote, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Vote();
		$activity->setUser($vote->getUser());
		$activity->setVote($vote);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createJoinActivity(\Ladb\CoreBundle\Entity\Join $join, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Join();
		$activity->setUser($join->getUser());
		$activity->setJoin($join);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createWriteActivity(\Ladb\CoreBundle\Entity\Message\Message $message, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Activity\Write();
		$activity->setUser($message->getSender());
		$activity->setMessage($message);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	// Delete /////

	public function deleteActivitiesByComment(\Ladb\CoreBundle\Entity\Comment $comment, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Comment::CLASS_NAME);
		$activities = $activityRepository->findByComment($comment);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByValue(\Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue $value, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Contribute::CLASS_NAME);
		$activities = $activityRepository->findByValue($value);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByFollower(\Ladb\CoreBundle\Entity\Follower $follower, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Follow::CLASS_NAME);
		$activities = $activityRepository->findByFollower($follower);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByLike(\Ladb\CoreBundle\Entity\Like $like, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Like::CLASS_NAME);
		$activities = $activityRepository->findByLike($like);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByVote(\Ladb\CoreBundle\Entity\Vote $vote, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Vote::CLASS_NAME);
		$activities = $activityRepository->findByVote($vote);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByJoin(\Ladb\CoreBundle\Entity\Join $join, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Join::CLASS_NAME);
		$activities = $activityRepository->findByJoin($join);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByMessage(\Ladb\CoreBundle\Entity\Message\Message $message, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Write::CLASS_NAME);
		$activities = $activityRepository->findByMessage($message);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByEntityTypeAndEntityId($entityType, $entityId, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Publish::CLASS_NAME);
		$activities = $activityRepository->findByEntityTypeAndEntityId($entityType, $entityId);
		$this->_deleteActivities($activities, $flush);
	}

	// Transfer /////

	public function transferPublishActivities($entityTypeSrc, $entityIdSrc, $entityTypeDest, $entityIdDest, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Activity\Publish::CLASS_NAME);
		$activities = $activityRepository->findByEntityTypeAndEntityId($entityTypeSrc, $entityIdSrc);

		foreach ($activities as $activity) {
			$activity->setEntityType($entityTypeDest);
			$activity->setEntityId($entityIdDest);
		}

		if ($flush) {
			$this->om->flush();
		}
	}

}