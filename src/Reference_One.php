<?php

// vim:ts=4:sw=4:et:fdm=marker:fdl=0

namespace atk4\data;

/**
 * Class description?
 */
class Reference_One extends Reference
{
    /**
     * Points to the join if we are part of one.
     *
     * @var Join|null
     */
    protected $join = null;

    /**
     * Default value of field.
     *
     * @var mixed
     */
    public $default = null;

    /**
     * Setting this to true will never actually store
     * the field in the database. It will action as normal,
     * but will be skipped by update/insert.
     *
     * @var bool
     */
    public $never_persist = false;

    /**
     * Is field read only?
     * Field value may not be changed. It'll never be saved.
     * For example, expressions are read only.
     *
     * @var bool
     */
    public $read_only = false;

    /**
     * Array with UI flags like editable, visible and hidden.
     *
     * By default hasOne relation ID field should be editable in forms,
     * but not visible in grids. UI should respect these flags.
     *
     * @var array
     */
    public $ui = [];

    /**
     * Is field mandatory? By default fields are not mandatory.
     *
     * @var bool|string
     */
    public $mandatory = false;

    /**
     * Should we use typecasting when saving/loading data to/from persistence.
     *
     * Value can be array [$typecast_save_callback, $typecast_load_callback].
     *
     * @var null|bool|array
     */
    public $typecast = null;

    /**
     * Should we use serialization when saving/loading data to/from persistence.
     *
     * Value can be array [$encode_callback, $decode_callback].
     *
     * @var null|bool|array
     */
    public $serialize = null;

    /**
     * Persisting format for type = 'date', 'datetime', 'time' fields.
     *
     * For example, for date it can be 'Y-m-d', for datetime - 'Y-m-d H:i:s' etc.
     *
     * @var string
     */
    public $persist_format = null;

    /**
     * Persisting timezone for type = 'date', 'datetime', 'time' fields.
     *
     * For example, 'IST', 'UTC', 'Europe/Riga' etc.
     *
     * @var string
     */
    public $persist_timezone = 'UTC';

    /**
     * DateTime class used for type = 'data', 'datetime', 'time' fields.
     *
     * For example, 'DateTime', 'Carbon' etc.
     *
     * @param string
     */
    public $dateTimeClass = 'DateTime';

    /**
     * Timezone class used for type = 'data', 'datetime', 'time' fields.
     *
     * For example, 'DateTimeZone', 'Carbon' etc.
     *
     * @param string
     */
    public $dateTimeZoneClass = 'DateTimeZone';

    /**
     * Reference_One will also add a field corresponding
     * to 'our_field' unless it exists of course.
     */
    public function init()
    {
        parent::init();

        if (!$this->our_field) {
            $this->our_field = $this->link;
        }

        if (!$this->owner->hasElement($this->our_field)) {
            $this->owner->addField($this->our_field, [
                'type'              => null, // $this->guessFieldType(),
                //'system'          => true,
                'join'              => $this->join,
                'default'           => $this->default,
                'never_persist'     => $this->never_persist,
                'read_only'         => $this->read_only,
                'ui'                => $this->ui,
                'mandatory'         => $this->mandatory,
                'typecast'          => $this->typecast,
                'serialize'         => $this->serialize,
                'persist_format'    => $this->persist_format,
                'persist_timezone'  => $this->persist_timezone,
                'dateTimeClass'     => $this->dateTimeClass,
                'dateTimeZoneClass' => $this->dateTimeZoneClass,
            ]);
        }
    }

    /**
     * Returns our field or id field.
     *
     * @return Field
     */
    protected function referenceOurValue()
    {
        $this->owner->persistence_data['use_table_prefixes'] = true;

        return $this->owner->getElement($this->our_field);
    }

    /**
     * If owner model is loaded, then return referenced model with respective record loaded.
     *
     * If owner model is not loaded, then return referenced model with condition set.
     * This can happen in case of deep traversal $m->ref('Many')->ref('one_id'), for example.
     *
     * @param array $defaults Properties
     *
     * @return Model
     */
    public function ref($defaults = [])
    {
        $m = $this->getModel($defaults);

        // add hook to set our_field = null when record of referenced model is deleted
        $m->addHook('afterDelete', function ($m) {
            $this->owner[$this->our_field] = null;
        });

        // if owner model is loaded, then try to load referenced model
        if ($this->their_field) {
            if ($this->owner[$this->our_field]) {
                $m->tryLoadBy($this->their_field, $this->owner[$this->our_field]);
            }

            return
                $m->addHook('afterSave', function ($m) {
                    $this->owner[$this->our_field] = $m[$this->their_field];
                });
        } else {
            if ($this->owner[$this->our_field]) {
                $m->tryLoad($this->owner[$this->our_field]);
            }

            return
                $m->addHook('afterSave', function ($m) {
                    $this->owner[$this->our_field] = $m->id;
                });
        }

        // can not load referenced model or set conditions on it, so we just return it
        return $m;
    }
}
