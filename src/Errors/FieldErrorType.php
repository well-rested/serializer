<?php

declare(strict_types=1);

namespace WellRested\Serializer\Errors;

enum FieldErrorType: string
{
	case ValueIsRequired = 'value_is_required';
	case ValueIsInvalidType = 'value_is_invalid_type';
	case InvalidCollectionKeyType = 'invalid_collection_key_type';
	case UnsatisfiableUnionType = 'unsatisfiable_union_type';
	case MissingPolymorphicTypeDiscriminator = 'missing_polymorphic_type_discriminator';
}
