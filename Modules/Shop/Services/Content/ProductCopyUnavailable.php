<?php

namespace Modules\Shop\Services\Content;

use RuntimeException;

/** The writing model could not give usable text this time. The item is simply tried again later. */
class ProductCopyUnavailable extends RuntimeException {}
