package com.aionemu.gameserver.model.templates.bonus_service;

import javax.xml.bind.annotation.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Created by Ace on 31/07/2016.
 */

@XmlAccessorType(XmlAccessType.FIELD)
@XmlType(name = "BonusServiceAttr", propOrder = {"bonusAttr"})
public class BonusServiceAttr
{
    @XmlElement(name = "bonus_attr")
    protected List<BonusPenaltyAttr> bonusAttr;

    @XmlAttribute(name = "buff_id", required = true)
    protected int buffId;

    public List<BonusPenaltyAttr> getPenaltyAttr() {
        if (bonusAttr == null) {
            bonusAttr = new ArrayList<BonusPenaltyAttr>();
        }
        return bonusAttr;
    }

    public int getBuffId() {
        return buffId;
    }

    public void setBuffId(int value) {
        buffId = value;
    }
}
