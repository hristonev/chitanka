<?php

namespace Chitanka\LibBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;
use Chitanka\LibBundle\Entity\TextRatingRepository;

class TextRatingType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('text', 'hidden', array(
				'data' => $options['data']->getText()->getId(),
				'mapped' => false,
			))
			//->add('user', 'hidden')
			->add('rating', 'choice', array(
				'choices' => TextRatingRepository::$ratings,
				'required' => false,
			));
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'Chitanka\LibBundle\Entity\TextRating',
		));
	}

	public function getName()
	{
		return 'text_rating';
	}
}
