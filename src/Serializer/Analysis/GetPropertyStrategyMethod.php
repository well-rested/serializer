<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

enum GetPropertyStrategyMethod: string
{
	case PublicGetter = 'public_getter';
	case GetterMethod = 'getter_method';
	case NotAvailable = 'not_available';
}
