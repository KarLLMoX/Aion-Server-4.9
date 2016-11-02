/**
 * This file is part of Aion-Lightning <aion-lightning.org>.
 *
 *  Aion-Lightning is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Aion-Lightning is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details. *
 *  You should have received a copy of the GNU General Public License
 *  along with Aion-Lightning.
 *  If not, see <http://www.gnu.org/licenses/>.
 */
package com.aionemu.gameserver.configs.main;

import com.aionemu.commons.configuration.Property;

/**
 * @author Falke_34
 */
public class AbyssBossesConfig {

    /**
     * Diflodox Spawn Time
     */
    @Property(key = "gameserver.diflodox.time", defaultValue = "0 0 22 ? * SUN") //TODO
    public static String DIFLODOX_SPAWN_SCHEDULE;

    /**
     * Diflonax Spawn Time
     */
    @Property(key = "gameserver.diflonax.time", defaultValue = "0 0 22 ? * SUN") //TODO
    public static String DIFLONAX_SPAWN_SCHEDULE;
}
