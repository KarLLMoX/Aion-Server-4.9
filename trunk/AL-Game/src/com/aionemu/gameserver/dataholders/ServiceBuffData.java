package com.aionemu.gameserver.dataholders;

import com.aionemu.gameserver.model.templates.bonus_service.BonusServiceAttr;
import gnu.trove.map.hash.TIntObjectHashMap;

import javax.xml.bind.Unmarshaller;
import javax.xml.bind.annotation.*;
import java.util.List;

/**
 * Created by Ace on 31/07/2016.
 */

@XmlAccessorType(XmlAccessType.FIELD)
@XmlType(name = "", propOrder = {"serviceBonusattr"})
@XmlRootElement(name = "service_bonusattrs")
public class ServiceBuffData
{
    @XmlElement(name = "service_bonusattr")
    protected List<BonusServiceAttr> serviceBonusattr;

    @XmlTransient
    private TIntObjectHashMap<BonusServiceAttr> templates = new TIntObjectHashMap<BonusServiceAttr>();

    void afterUnmarshal(Unmarshaller u, Object parent) {
        for (BonusServiceAttr template: serviceBonusattr) {
            templates.put(template.getBuffId(), template);
        }
        serviceBonusattr.clear();
        serviceBonusattr = null;
    }

    public int size() {
        return templates.size();
    }

    public BonusServiceAttr getInstanceBonusattr(int buffId) {
        return templates.get(buffId);
    }
}
