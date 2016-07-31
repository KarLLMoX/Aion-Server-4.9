package com.aionemu.gameserver.dataholders;

import com.aionemu.gameserver.model.templates.bonus_service.PlayersBonusServiceAttr;
import gnu.trove.map.hash.TIntObjectHashMap;

import javax.xml.bind.Unmarshaller;
import javax.xml.bind.annotation.*;
import java.util.List;

/**
 * Created by Ace on 31/07/2016.
 */

@XmlAccessorType(XmlAccessType.FIELD)
@XmlType(name = "", propOrder = {"playersServiceBonusattr"})
@XmlRootElement(name = "players_service_bonusattrs")
public class PlayersBonusData
{
    @XmlElement(name = "players_service_bonusattr")
    protected List<PlayersBonusServiceAttr> playersServiceBonusattr;

    @XmlTransient
    private TIntObjectHashMap<PlayersBonusServiceAttr> templates = new TIntObjectHashMap<PlayersBonusServiceAttr>();

    void afterUnmarshal(Unmarshaller u, Object parent) {
        for (PlayersBonusServiceAttr template : playersServiceBonusattr) {
            templates.put(template.getBuffId(), template);
        }
        playersServiceBonusattr.clear();
        playersServiceBonusattr = null;
    }

    public int size() {
        return templates.size();
    }

    public PlayersBonusServiceAttr getInstanceBonusattr(int buffId) {
        return templates.get(buffId);
    }
}
