<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

enum SetPropertyStrategyMethod: string
{
	case ConstructorArgument = 'constructor_argument';
	case PublicSetter = 'public_setter';
	case SetterMethod = 'setter_method';
	case NotAvailable = 'not_available';
}
