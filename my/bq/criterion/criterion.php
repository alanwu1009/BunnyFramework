<?php

namespace my\bq\criterion;

interface Criterion {
	
	/**
	 * Render the SQL fragment
	 * @author Alan Wu.
	 */
	public function toSqlString($criteria);
	
	
} 