<?php

return ['pending_limit' => max(1, (int) env('GENERATION_PENDING_LIMIT', 10))];
