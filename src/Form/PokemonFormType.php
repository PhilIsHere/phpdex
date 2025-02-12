<?php
/*
 * */

namespace App\Form;

use App\Entity\Pokemon;
use App\Entity\PokemonTypes;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PokemonFormType extends AbstractType {
    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(private readonly ParameterBagInterface $parameterBag, private Security $security,
                                private TranslatorInterface            $translator) {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $isAdmin = in_array("ROLE_ADMIN", $this->security->getUser()->getRoles());

        $builder
            ->add('pokedexId', NumberType::class, [
                'invalid_message' => 'Die Pokedex Nummer gibt es nicht! Gültige Werte: 1 - 905',
                'constraints' => [
                    new Regex([
                        'pattern' => $this->parameterBag->get('PKMN_RANGE'),
                        'match' => 'true',
                        'message' => 'Confirm: PKMN_Range'
                    ])
                ]
            ])
            ->add('name', TextType::class, [
                'constraints' => [
                    new Regex([
                        'pattern' => $this->parameterBag->get('LETTERS_ONLY'),
                        'match' => 'true',
                        'message' => 'Confirm: LETTERS_ONLY!'
                    ])]])
            ->add('pokemonType', EntityType::class, [
                'placeholder' => 'Choose a type',
                'multiple' => true,
                'expanded' => true,
                'class' => PokemonTypes::class,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('p')->orderBy('p.typeName', 'ASC');
                },
                'choice_translation_domain' => true,
                'choice_label' => 'typeName',
            ])
            ->add('height', NumberType::class, [
                'invalid_message' => 'Bitte gib ausschließlich Zahlen im "Height"-Feld ein!',
                'constraints' => [
                    new Regex([
                        'pattern' => $this->parameterBag->get('DIGITS_ONLY'),
                        'match' => 'true',
                        'message' => 'Comfirm: DIGITS_ONLY'
                    ]),
                ]])
            ->add('imageFile', FileType::class, [
                'label' => 'Pokemon Image',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '512k',
                        'mimeTypes' => [
                            'image/jpg',
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Erlaubte Dateitypen: jpg, jpeg, png, gif',
                        'maxSizeMessage' => 'Die Datei darf maximal 1MB groß sein!'
                    ])
                ]
            ]);
        if ($isAdmin) {
            $builder->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Ich verstehe, dass die Änderungen nicht durch User oder API geändert werden können!',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Hast du verstanden, dass es sich um eine Admin-Änderung handelt?.',
                    ]),
                ],
            ]);
        }
        $builder->add('absenden', SubmitType::class);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => Pokemon::class,
        ]);
    }
}
