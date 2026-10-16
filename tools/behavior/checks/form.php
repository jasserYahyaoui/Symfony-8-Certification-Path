<?php

declare(strict_types=1);

/*
 * Remediation P1.2 (F8, part): CollectionType and data mapping, executed on
 * Form 8.0.
 *
 * The extra-entries check uses the Validator extension: « This form should not
 * contain extra fields. » is a violation of the Form constraint, so a factory
 * without that extension accepts the submission and silently drops the entry.
 */

namespace CertBehavior\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

/** A post whose tags are an object collection, with an adder and a remover. */
final class TaggedPost
{
    /** @var list<string> every mutator called */
    public array $calls = [];
    private \ArrayObject $tags;

    public function __construct()
    {
        $this->tags = new \ArrayObject(['a', 'b']);
    }

    public function getTags(): \ArrayObject
    {
        return $this->tags;
    }

    public function setTags(\ArrayObject $tags): void
    {
        $this->calls[] = 'set';
        $this->tags = $tags;
    }

    public function addTag(string $tag): void
    {
        $this->calls[] = "add:$tag";
        $this->tags[] = $tag;
    }

    public function removeTag(string $tag): void
    {
        $this->calls[] = "remove:$tag";
        foreach ($this->tags as $key => $value) {
            if ($value === $tag) {
                unset($this->tags[$key]);
            }
        }
    }
}

/** The same collection, with a setter only. */
final class SetterPost
{
    /** @var list<string> */
    public array $calls = [];
    private \ArrayObject $tags;

    public function __construct()
    {
        $this->tags = new \ArrayObject(['a', 'b']);
    }

    public function getTags(): \ArrayObject
    {
        return $this->tags;
    }

    public function setTags(\ArrayObject $tags): void
    {
        $this->calls[] = 'set';
        $this->tags = $tags;
    }
}

final class Author
{
    public ?string $name = 'old';
    public ?string $city = null;
    public Profile $profile;

    public function __construct()
    {
        $this->profile = new Profile();
    }
}

final class Profile
{
    public ?string $bio = null;
}

final class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('city', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['inherit_data' => true]);
    }
}

$factory = static fn (bool $validator = false): FormFactoryInterface => $validator
    ? Forms::createFormFactoryBuilder()->addExtension(new ValidatorExtension(Validation::createValidator()))->getFormFactory()
    : Forms::createFormFactory();

/** Submits a tags collection; returns [data, valid, error messages]. */
$submitTags = static function (FormFactoryInterface $factory, array $options, array $submitted): array {
    $form = $factory->createBuilder(FormType::class, ['tags' => ['a', 'b']])
        ->add('tags', CollectionType::class, ['entry_type' => TextType::class] + $options)
        ->getForm();
    $form->submit(['tags' => $submitted]);
    $errors = [];
    foreach ($form->getErrors(true) as $error) {
        $errors[] = $error->getMessage();
    }

    return [$form->getData()['tags'], $form->isValid(), $errors];
};

return [
    'form.collection-extra-entries-refused' => static function () use ($factory, $submitTags): ?string {
        [$data, $valid, $errors] = $submitTags($factory(true), [], ['a', 'b', 'c']);
        if (['a', 'b'] !== $data || $valid || ['This form should not contain extra fields.'] !== $errors) {
            return 'without allow_add: '.json_encode([$data, $valid, $errors]);
        }
        [$data, $valid] = $submitTags($factory(true), ['allow_add' => true], ['a', 'b', 'c']);

        return ['a', 'b', 'c'] === $data && $valid ? null : 'with allow_add: '.json_encode($data);
    },

    'form.collection-delete-needs-allow-delete' => static function () use ($factory, $submitTags): ?string {
        $kept = $submitTags($factory(), [], ['a'])[0];
        $removed = $submitTags($factory(), ['allow_delete' => true], ['a'])[0];
        $emptyKept = $submitTags($factory(), ['allow_delete' => true], ['a', ''])[0];
        $emptyRemoved = $submitTags($factory(), ['allow_delete' => true, 'delete_empty' => true], ['a', ''])[0];

        return match (true) {
            ['a', null] !== $kept => 'without allow_delete: '.json_encode($kept),
            ['a'] !== $removed => 'with allow_delete: '.json_encode($removed),
            ['a', null] !== $emptyKept => 'empty entry without delete_empty: '.json_encode($emptyKept),
            ['a'] !== $emptyRemoved => 'empty entry with delete_empty: '.json_encode($emptyRemoved),
            default => null,
        };
    },

    'form.collection-prototype' => static function () use ($factory): ?string {
        $view = static fn (array $options) => $factory()->createBuilder(FormType::class)
            ->add('tags', CollectionType::class, ['entry_type' => TextType::class] + $options)
            ->getForm()->createView()['tags'];
        $with = $view(['allow_add' => true]);
        $without = $view([]);
        $named = $view(['allow_add' => true, 'prototype_name' => '__tag__']);

        return match (true) {
            !isset($with->vars['prototype']) => 'no prototype with allow_add',
            '__name__' !== $with->vars['prototype']->vars['name'] => 'prototype name is '.$with->vars['prototype']->vars['name'],
            'form[tags][__name__]' !== $with->vars['prototype']->vars['full_name'] => 'prototype full_name is '.$with->vars['prototype']->vars['full_name'],
            isset($without->vars['prototype']) => 'a prototype without allow_add',
            '__tag__' !== $named->vars['prototype']->vars['name'] => 'prototype_name ignored',
            default => null,
        };
    },

    'form.by-reference-object-collection' => static function () use ($factory): ?string {
        $results = [];
        foreach ([[TaggedPost::class, true], [TaggedPost::class, false], [SetterPost::class, true], [SetterPost::class, false]] as [$class, $byReference]) {
            $post = new $class();
            $before = $post->getTags();
            $factory()->createBuilder(FormType::class, $post, ['data_class' => $class])
                ->add('tags', CollectionType::class, ['entry_type' => TextType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => $byReference])
                ->getForm()->submit(['tags' => ['a', 'c']]);
            $results[] = [array_values($post->getTags()->getArrayCopy()), $post->calls, $before === $post->getTags()];
        }
        $want = [
            [['a', 'c'], [], true],
            [['a', 'c'], ['remove:b', 'add:c'], true],
            [['a', 'c'], [], true],
            [['a', 'c'], ['set'], false],
        ];

        return $want === $results ? null : 'got '.json_encode($results);
    },

    'form.property-path-and-inherit-data' => static function () use ($factory): ?string {
        $author = new Author();
        $form = $factory()->createBuilder(FormType::class, $author, ['data_class' => Author::class])
            ->add('title', TextType::class, ['property_path' => 'name'])
            ->add('bio', TextType::class, ['property_path' => 'profile.bio'])
            ->add('address', AddressType::class)
            ->getForm();
        $initial = $form->get('title')->getData();
        $form->submit(['title' => 'New', 'bio' => 'Hi', 'address' => ['city' => 'Lyon']]);

        return match (true) {
            'old' !== $initial => "title read $initial, not the name property",
            'New' !== $author->name || 'Hi' !== $author->profile->bio => 'property_path did not write name and profile.bio',
            'Lyon' !== $author->city => 'the inherit_data subform did not write on the parent object',
            $form->get('address')->getData() !== $author => 'the inherit_data subform does not hold the parent object',
            default => null,
        };
    },
];
