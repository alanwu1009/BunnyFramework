<?php

namespace my\bq\criterion;

interface MongoCriterion {
	
	/**
	 * Render the SQL fragment
	 * @author Alan Wu.
	 */
	public function toMongoParam($criteria);


} 